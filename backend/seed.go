package main

import (
	"context"
	"math/rand"
)

func randInt(n int) int { return rand.Intn(n) }

func (s *Store) seedIfEmpty(ctx context.Context) error {
	var n int
	if err := s.pool.QueryRow(ctx, "SELECT COUNT(*) FROM people").Scan(&n); err != nil {
		return err
	}
	if n > 0 {
		return nil
	}

	handles := []string{
		"karina", "Atos", "Emilio", "adel", "troumpl", "Lara", "sita", "AGA", "Natash", "Shree",
		"Greg0", "Anna", "thebest", "Deisi", "shlomi", "CREA", "Liang", "Anela", "Sunny", "MAKC",
		"Raven", "czamac", "CU", "Epsi", "Suzan", "MISHELL", "Neon", "shark", "Sieger", "Bhkav",
		"Julia", "Gnom", "RRfdur", "Vega", "Miko", "Dasha", "Enzo", "Priya", "Kaan", "Yuki",
		"Ravi", "Lena", "Tomer", "Bruno", "Elif", "Nastya", "Pablo", "Hana", "Igor", "Maya",
		"Deniz", "Sofia", "Omar", "Wei", "Aylin", "Diego", "Noa", "Timur", "Chiara", "Kenji",
	}
	topics := []string{"Soccer", "Music", "Movies", "Travel", "Gaming", "Art", "Tech", "Love", "Friendship"}
	regions := []string{"Brazil", "Israel", "USA", "Germany", "Russia", "Turkey", "Azerbaijan", "France", "Japan", "Spain"}
	languages := []string{"English", "Russian", "German", "Turkish", "Hebrew", "Portuguese", "French", "Spanish"}
	jobs := []string{"Student", "Engineer", "Artist", "Teacher", "Doctor", "Gamer", "Designer", "Musician"}
	statuses := []string{"Online", "Online", "Online", "Away", "Busy", "Invisible"}
	moods := []string{"Happy", "Bored", "Excited", "Sad", "Chill", "Curious"}
	intents := []string{"Chat", "Friendship", "Dating", "Networking"}
	zodiacs := []string{"Aries", "Taurus", "Gemini", "Cancer", "Leo", "Virgo", "Libra", "Scorpio", "Sagittarius", "Capricorn", "Aquarius", "Pisces"}
	sprites := []string{"gold", "orange", "blue", "green", "pink"}
	taglines := []string{
		"Looking for people to chat with :)", "Say hi if you like the same music",
		"New here, be nice!", "Anyone up for a game?", "Traveling the world one chat at a time",
		"Coffee + code = life", "Movie buff, recommend me something", "Just here for good vibes",
		"Tell me a joke", "Practicing my English", "Football is life ⚽", "Night owl \U0001f989",
	}
	friends := map[string]bool{"karina": true, "Lara": true, "Anna": true, "Bruno": true}

	tx, err := s.pool.Begin(ctx)
	if err != nil {
		return err
	}
	defer tx.Rollback(ctx)

	lower := func(s string) string { return toLower(s) }
	for i, h := range handles {
		gender := "Male"
		if i%2 == 0 {
			gender = "Female"
		}
		_, err := tx.Exec(ctx, `INSERT INTO people
			(handle,display_name,odigo_id,age,gender,region,language,occupation,topic,status,mood,intention,zodiac,sprite,tagline,is_friend)
			VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16)`,
			h, h, lower(h)+"@odigo.im", 18+(i*7)%42, gender,
			regions[i%len(regions)], languages[i%len(languages)], jobs[i%len(jobs)], topics[i%len(topics)],
			statuses[i%len(statuses)], moods[i%len(moods)], intents[i%len(intents)], zodiacs[i%len(zodiacs)],
			sprites[i%len(sprites)], taglines[i%len(taglines)], friends[h])
		if err != nil {
			return err
		}
	}

	seed := []struct {
		peer, dir, body string
		min             int
	}{
		{"karina", "in", "Hey! I saw you in the Soccer room :)", 42},
		{"karina", "out", "Haha yeah, who do you support?", 40},
		{"karina", "in", "Barcelona all the way! You?", 38},
		{"karina", "out", "Real Madrid — we can still be friends though", 36},
		{"Lara", "in", "thanks for adding me!", 20},
		{"Lara", "out", "of course, welcome to my list", 18},
		{"Anna", "in", "what music are you into?", 10},
	}
	for _, m := range seed {
		if _, err := tx.Exec(ctx,
			`INSERT INTO odigo_messages(peer,direction,type,body,created_at) VALUES($1,$2,'Message',$3, now() - make_interval(mins => $4))`,
			m.peer, m.dir, m.body, m.min); err != nil {
			return err
		}
	}
	return tx.Commit(ctx)
}

func toLower(s string) string {
	b := []byte(s)
	for i, c := range b {
		if c >= 'A' && c <= 'Z' {
			b[i] = c + 32
		}
	}
	return string(b)
}
