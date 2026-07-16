<?php

namespace App\Http\Controllers;

use App\Models\OdigoMessage;
use App\Models\Person;
use Illuminate\Http\Request;

class OdigoController extends Controller
{
    /** Me — the logged-in Odigo user (mock). */
    private array $me = [
        'handle'   => 'ventura',
        'odigo_id' => 'ventura@odigo.im',
    ];

    public function index()
    {
        return view('odigo', ['me' => $this->me]);
    }

    /** Distinct filter option lists, built from the seeded data. */
    public function filters()
    {
        return response()->json([
            'topic'      => array_merge(['All Topics'], Person::query()->distinct()->orderBy('topic')->pluck('topic')->all()),
            'ageGroup'   => ['Any', '18-23', '24-29', '30-39', '40-49', '50+'],
            'gender'     => ['Any', 'Female', 'Male'],
            'region'     => array_merge(['Worldwide'], Person::query()->distinct()->orderBy('region')->pluck('region')->all()),
            'language'   => array_merge(['Any'], Person::query()->distinct()->orderBy('language')->pluck('language')->all()),
            'occupation' => array_merge(['Any'], Person::query()->distinct()->orderBy('occupation')->pluck('occupation')->all()),
            'status'     => ['Any', 'Online', 'Away', 'Busy', 'Invisible'],
            'mood'       => array_merge(['Any'], Person::query()->distinct()->orderBy('mood')->pluck('mood')->all()),
            'intention'  => array_merge(['Any'], Person::query()->distinct()->orderBy('intention')->pluck('intention')->all()),
            'zodiac'     => array_merge(['Any'], Person::query()->distinct()->orderBy('zodiac')->pluck('zodiac')->all()),
        ]);
    }

    /** Filtered people for the People Finder radar (paginated by 10). */
    public function people(Request $request)
    {
        $q = Person::query();

        if (($t = $request->query('topic')) && $t !== 'All Topics') {
            $q->where('topic', $t);
        }
        $this->applyAgeGroup($q, $request->query('ageGroup'));
        $this->eq($q, 'gender', $request->query('gender'), 'Any');
        if (($r = $request->query('region')) && $r !== 'Worldwide') {
            $q->where('region', $r);
        }
        $this->eq($q, 'language', $request->query('language'), 'Any');
        $this->eq($q, 'occupation', $request->query('occupation'), 'Any');
        $this->eq($q, 'status', $request->query('status'), 'Any');
        $this->eq($q, 'mood', $request->query('mood'), 'Any');
        $this->eq($q, 'intention', $request->query('intention'), 'Any');
        $this->eq($q, 'zodiac', $request->query('zodiac'), 'Any');

        if ($search = trim((string) $request->query('search'))) {
            $q->where(function ($sub) use ($search) {
                $sub->where('display_name', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        $total = (clone $q)->count();
        $page  = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $people = $q->orderBy('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (Person $p) => $this->card($p));

        return response()->json([
            'people'   => $people,
            'total'    => $total,
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    /** Full profile for the Details window. */
    public function person(string $handle)
    {
        $p = Person::where('handle', $handle)->firstOrFail();

        return response()->json([
            'handle'       => $p->handle,
            'display_name' => $p->display_name,
            'odigo_id'     => $p->odigo_id,
            'age'          => $p->age,
            'ageRange'     => $this->ageRange($p->age),
            'gender'       => $p->gender,
            'region'       => $p->region,
            'language'     => $p->language,
            'occupation'   => $p->occupation,
            'topic'        => $p->topic,
            'status'       => $p->status,
            'mood'         => $p->mood,
            'intention'    => $p->intention,
            'zodiac'       => $p->zodiac,
            'sprite'       => $p->sprite,
            'tagline'      => $p->tagline,
            'is_friend'    => $p->is_friend,
        ]);
    }

    /** Live counts for the Yahoo-mini status widget. */
    public function stats()
    {
        return response()->json([
            'people'    => Person::whereIn('status', ['Online', 'Away', 'Busy'])->count(),
            'invisible' => Person::where('status', 'Invisible')->count(),
            'notes'     => OdigoMessage::where('direction', 'in')->count(),
            'friends'   => Person::where('is_friend', true)->count(),
        ]);
    }

    /** Conversation history with a contact. */
    public function history(string $handle)
    {
        $messages = OdigoMessage::where('peer', $handle)
            ->orderBy('created_at')
            ->get()
            ->map(fn (OdigoMessage $m) => [
                'direction' => $m->direction,
                'type'      => $m->type,
                'body'      => $m->body,
                'time'      => $m->created_at->format('H:i'),
            ]);

        return response()->json(['peer' => $handle, 'messages' => $messages]);
    }

    /** Send a message (persisted). Auto-generates a mock reply. */
    public function send(Request $request)
    {
        $data = $request->validate([
            'to'   => 'required|string',
            'body' => 'required|string|max:2000',
            'type' => 'nullable|string',
        ]);

        OdigoMessage::create([
            'peer'      => $data['to'],
            'direction' => 'out',
            'type'      => $data['type'] ?? 'Message',
            'body'      => $data['body'],
        ]);

        // Mock auto-reply so the conversation feels alive.
        $replies = [
            'nice to hear from you!', 'lol ok', 'brb', 'who is this? :)',
            'cool, tell me more', 'haha', 'sure, sounds good', 'adding you now',
        ];
        OdigoMessage::create([
            'peer'      => $data['to'],
            'direction' => 'in',
            'type'      => 'Message',
            'body'      => $replies[array_rand($replies)],
            'created_at' => now()->addSecond(),
            'updated_at' => now()->addSecond(),
        ]);

        return response()->json(['ok' => true, 'status' => 'Message sent to ' . $data['to']]);
    }

    /** Toggle a contact as friend. */
    public function addFriend(Request $request)
    {
        $data = $request->validate(['handle' => 'required|string']);
        $p = Person::where('handle', $data['handle'])->firstOrFail();
        $p->is_friend = true;
        $p->save();

        return response()->json(['ok' => true, 'status' => $p->display_name . ' added to your friends']);
    }

    // ---- helpers -------------------------------------------------------

    private function card(Person $p): array
    {
        return [
            'handle'       => $p->handle,
            'display_name' => $p->display_name,
            'odigo_id'     => $p->odigo_id,
            'status'       => $p->status,
            'mood'         => $p->mood,
            'sprite'       => $p->sprite,
            'topic'        => $p->topic,
        ];
    }

    private function eq($q, string $col, ?string $val, string $anyToken): void
    {
        if ($val !== null && $val !== '' && $val !== $anyToken) {
            $q->where($col, $val);
        }
    }

    private function applyAgeGroup($q, ?string $group): void
    {
        $map = [
            '18-23' => [18, 23], '24-29' => [24, 29], '30-39' => [30, 39],
            '40-49' => [40, 49], '50+' => [50, 120],
        ];
        if ($group && isset($map[$group])) {
            $q->whereBetween('age', $map[$group]);
        }
    }

    private function ageRange(int $age): string
    {
        foreach (['18-23' => [18, 23], '24-29' => [24, 29], '30-39' => [30, 39], '40-49' => [40, 49], '50+' => [50, 120]] as $label => [$lo, $hi]) {
            if ($age >= $lo && $age <= $hi) {
                return $label;
            }
        }
        return (string) $age;
    }
}
