package main

import (
	"context"
	"fmt"
	"strings"

	"github.com/jackc/pgx/v5/pgxpool"
)

type Store struct{ pool *pgxpool.Pool }

func NewStore(ctx context.Context, dsn string) (*Store, error) {
	pool, err := pgxpool.New(ctx, dsn)
	if err != nil {
		return nil, err
	}
	if err := pool.Ping(ctx); err != nil {
		return nil, err
	}
	s := &Store{pool: pool}
	if err := s.initSchema(ctx); err != nil {
		return nil, err
	}
	if err := s.seedIfEmpty(ctx); err != nil {
		return nil, err
	}
	return s, nil
}

func (s *Store) initSchema(ctx context.Context) error {
	_, err := s.pool.Exec(ctx, `
CREATE TABLE IF NOT EXISTS people (
	id           BIGSERIAL PRIMARY KEY,
	handle       TEXT UNIQUE NOT NULL,
	display_name TEXT NOT NULL,
	odigo_id     TEXT NOT NULL,
	age          INT  NOT NULL,
	gender       TEXT NOT NULL,
	region       TEXT NOT NULL,
	language     TEXT NOT NULL,
	occupation   TEXT NOT NULL,
	topic        TEXT NOT NULL,
	status       TEXT NOT NULL DEFAULT 'Online',
	mood         TEXT NOT NULL DEFAULT 'Happy',
	intention    TEXT NOT NULL DEFAULT 'Chat',
	zodiac       TEXT NOT NULL,
	sprite       TEXT NOT NULL DEFAULT 'gold',
	tagline      TEXT,
	is_friend    BOOLEAN NOT NULL DEFAULT FALSE,
	created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE IF NOT EXISTS odigo_messages (
	id         BIGSERIAL PRIMARY KEY,
	peer       TEXT NOT NULL,
	direction  TEXT NOT NULL,
	type       TEXT NOT NULL DEFAULT 'Message',
	body       TEXT NOT NULL,
	created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);`)
	return err
}

// ----- filters -----

func (s *Store) distinct(ctx context.Context, col string) ([]string, error) {
	rows, err := s.pool.Query(ctx, fmt.Sprintf("SELECT DISTINCT %s FROM people ORDER BY %s", col, col))
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []string{}
	for rows.Next() {
		var v string
		if err := rows.Scan(&v); err != nil {
			return nil, err
		}
		out = append(out, v)
	}
	return out, rows.Err()
}

func (s *Store) Filters(ctx context.Context) (map[string]any, error) {
	get := func(col string, prefix ...string) []string {
		vals, _ := s.distinct(ctx, col)
		return append(append([]string{}, prefix...), vals...)
	}
	return map[string]any{
		"topic":      get("topic", "All Topics"),
		"ageGroup":   []string{"Any", "18-23", "24-29", "30-39", "40-49", "50+"},
		"gender":     []string{"Any", "Female", "Male"},
		"region":     get("region", "Worldwide"),
		"language":   get("language", "Any"),
		"occupation": get("occupation", "Any"),
		"status":     []string{"Any", "Online", "Away", "Busy", "Invisible"},
		"mood":       get("mood", "Any"),
		"intention":  get("intention", "Any"),
		"zodiac":     get("zodiac", "Any"),
	}, nil
}

// ----- people (filtered + paginated) -----

type PeopleQuery struct {
	Topic, AgeGroup, Gender, Region, Language, Occupation, Status, Mood, Intention, Zodiac, Search string
	Page                                                                                          int
}

var ageRanges = map[string][2]int{
	"18-23": {18, 23}, "24-29": {24, 29}, "30-39": {30, 39}, "40-49": {40, 49}, "50+": {50, 120},
}

func ageRange(age int) string {
	for _, k := range []string{"18-23", "24-29", "30-39", "40-49", "50+"} {
		r := ageRanges[k]
		if age >= r[0] && age <= r[1] {
			return k
		}
	}
	return fmt.Sprintf("%d", age)
}

type Card struct {
	Handle      string `json:"handle"`
	DisplayName string `json:"display_name"`
	OdigoID     string `json:"odigo_id"`
	Status      string `json:"status"`
	Mood        string `json:"mood"`
	Sprite      string `json:"sprite"`
	Topic       string `json:"topic"`
}

func (s *Store) People(ctx context.Context, q PeopleQuery) (cards []Card, total, page, pages int, err error) {
	var where []string
	var args []any
	add := func(cond string, val any) { args = append(args, val); where = append(where, fmt.Sprintf(cond, len(args))) }
	eq := func(col, val, anyTok string) {
		if val != "" && val != anyTok {
			add(col+" = $%d", val)
		}
	}
	if q.Topic != "" && q.Topic != "All Topics" {
		add("topic = $%d", q.Topic)
	}
	if r, ok := ageRanges[q.AgeGroup]; ok {
		args = append(args, r[0], r[1])
		where = append(where, fmt.Sprintf("age BETWEEN $%d AND $%d", len(args)-1, len(args)))
	}
	eq("gender", q.Gender, "Any")
	if q.Region != "" && q.Region != "Worldwide" {
		add("region = $%d", q.Region)
	}
	eq("language", q.Language, "Any")
	eq("occupation", q.Occupation, "Any")
	eq("status", q.Status, "Any")
	eq("mood", q.Mood, "Any")
	eq("intention", q.Intention, "Any")
	eq("zodiac", q.Zodiac, "Any")
	if s2 := strings.TrimSpace(q.Search); s2 != "" {
		args = append(args, "%"+s2+"%")
		where = append(where, fmt.Sprintf("(display_name ILIKE $%d OR topic ILIKE $%d)", len(args), len(args)))
	}
	clause := ""
	if len(where) > 0 {
		clause = " WHERE " + strings.Join(where, " AND ")
	}

	if err = s.pool.QueryRow(ctx, "SELECT COUNT(*) FROM people"+clause, args...).Scan(&total); err != nil {
		return
	}
	const perPage = 10
	page = q.Page
	if page < 1 {
		page = 1
	}
	pages = (total + perPage - 1) / perPage
	if pages < 1 {
		pages = 1
	}
	sql := "SELECT handle, display_name, odigo_id, status, mood, sprite, topic FROM people" +
		clause + fmt.Sprintf(" ORDER BY id LIMIT %d OFFSET %d", perPage, (page-1)*perPage)
	rows, e := s.pool.Query(ctx, sql, args...)
	if e != nil {
		err = e
		return
	}
	defer rows.Close()
	cards = []Card{}
	for rows.Next() {
		var c Card
		if err = rows.Scan(&c.Handle, &c.DisplayName, &c.OdigoID, &c.Status, &c.Mood, &c.Sprite, &c.Topic); err != nil {
			return
		}
		cards = append(cards, c)
	}
	err = rows.Err()
	return
}

// ----- person -----

func (s *Store) Person(ctx context.Context, handle string) (map[string]any, error) {
	var (
		dn, oid, gender, region, lang, occ, topic, status, mood, intent, zod, sprite string
		age                                                                          int
		tagline                                                                      *string
		isFriend                                                                     bool
	)
	err := s.pool.QueryRow(ctx, `SELECT display_name, odigo_id, age, gender, region, language,
		occupation, topic, status, mood, intention, zodiac, sprite, tagline, is_friend
		FROM people WHERE handle=$1`, handle).
		Scan(&dn, &oid, &age, &gender, &region, &lang, &occ, &topic, &status, &mood, &intent, &zod, &sprite, &tagline, &isFriend)
	if err != nil {
		return nil, err
	}
	tl := ""
	if tagline != nil {
		tl = *tagline
	}
	return map[string]any{
		"handle": handle, "display_name": dn, "odigo_id": oid, "age": age, "ageRange": ageRange(age),
		"gender": gender, "region": region, "language": lang, "occupation": occ, "topic": topic,
		"status": status, "mood": mood, "intention": intent, "zodiac": zod, "sprite": sprite,
		"tagline": tl, "is_friend": isFriend,
	}, nil
}

// ----- stats -----

func (s *Store) Stats(ctx context.Context) (map[string]int, error) {
	m := map[string]int{}
	q := func(dst *int, sql string) error { return s.pool.QueryRow(ctx, sql).Scan(dst) }
	var people, invis, notes, friends int
	if err := q(&people, "SELECT COUNT(*) FROM people WHERE status IN ('Online','Away','Busy')"); err != nil {
		return nil, err
	}
	if err := q(&invis, "SELECT COUNT(*) FROM people WHERE status='Invisible'"); err != nil {
		return nil, err
	}
	if err := q(&notes, "SELECT COUNT(*) FROM odigo_messages WHERE direction='in'"); err != nil {
		return nil, err
	}
	if err := q(&friends, "SELECT COUNT(*) FROM people WHERE is_friend=TRUE"); err != nil {
		return nil, err
	}
	m["people"], m["invisible"], m["notes"], m["friends"] = people, invis, notes, friends
	return m, nil
}

// ----- messages -----

type Msg struct {
	Direction string `json:"direction"`
	Type      string `json:"type"`
	Body      string `json:"body"`
	Time      string `json:"time"`
}

func (s *Store) History(ctx context.Context, peer string) ([]Msg, error) {
	rows, err := s.pool.Query(ctx, `SELECT direction, type, body, to_char(created_at,'HH24:MI')
		FROM odigo_messages WHERE peer=$1 ORDER BY created_at`, peer)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []Msg{}
	for rows.Next() {
		var m Msg
		if err := rows.Scan(&m.Direction, &m.Type, &m.Body, &m.Time); err != nil {
			return nil, err
		}
		out = append(out, m)
	}
	return out, rows.Err()
}

var mockReplies = []string{
	"nice to hear from you!", "lol ok", "brb", "who is this? :)",
	"cool, tell me more", "haha", "sure, sounds good", "adding you now",
}

func (s *Store) Send(ctx context.Context, to, body, typ string) error {
	if typ == "" {
		typ = "Message"
	}
	if _, err := s.pool.Exec(ctx,
		`INSERT INTO odigo_messages(peer,direction,type,body) VALUES($1,'out',$2,$3)`, to, typ, body); err != nil {
		return err
	}
	reply := mockReplies[randInt(len(mockReplies))]
	_, err := s.pool.Exec(ctx,
		`INSERT INTO odigo_messages(peer,direction,type,body,created_at) VALUES($1,'in','Message',$2, now()+interval '1 second')`, to, reply)
	return err
}

func (s *Store) AddFriend(ctx context.Context, handle string) (string, error) {
	var dn string
	err := s.pool.QueryRow(ctx, "UPDATE people SET is_friend=TRUE WHERE handle=$1 RETURNING display_name", handle).Scan(&dn)
	return dn, err
}
