<?php

namespace Database\Seeders;

use App\Models\OdigoMessage;
use App\Models\Person;
use Illuminate\Database\Seeder;

class OdigoSeeder extends Seeder
{
    public function run(): void
    {
        Person::query()->delete();
        OdigoMessage::query()->delete();

        $topics    = ['Soccer', 'Music', 'Movies', 'Travel', 'Gaming', 'Art', 'Tech', 'Love', 'Friendship'];
        $regions   = ['Brazil', 'Israel', 'USA', 'Germany', 'Russia', 'Turkey', 'Azerbaijan', 'France', 'Japan', 'Spain'];
        $languages = ['English', 'Russian', 'German', 'Turkish', 'Hebrew', 'Portuguese', 'French', 'Spanish'];
        $jobs      = ['Student', 'Engineer', 'Artist', 'Teacher', 'Doctor', 'Gamer', 'Designer', 'Musician'];
        $statuses  = ['Online', 'Online', 'Online', 'Away', 'Busy', 'Invisible'];
        $moods     = ['Happy', 'Bored', 'Excited', 'Sad', 'Chill', 'Curious'];
        $intents   = ['Chat', 'Friendship', 'Dating', 'Networking'];
        $zodiacs   = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];
        $sprites   = ['gold', 'orange', 'blue', 'green', 'pink'];
        $taglines  = [
            'Looking for people to chat with :)', 'Say hi if you like the same music',
            'New here, be nice!', 'Anyone up for a game?', 'Traveling the world one chat at a time',
            'Coffee + code = life', 'Movie buff, recommend me something', 'Just here for good vibes',
            'Tell me a joke', 'Practicing my English', 'Football is life ⚽', 'Night owl 🦉',
        ];

        $handles = [
            'karina', 'Atos', 'Emilio', 'adel', 'troumpl', 'Lara', 'sita', 'AGA', 'Natash', 'Shree',
            'Greg0', 'Anna', 'thebest', 'Deisi', 'shlomi', 'CREA', 'Liang', 'Anela', 'Sunny', 'MAKC',
            'Raven', 'czamac', 'CU', 'Epsi', 'Suzan', 'MISHELL', 'Neon', 'shark', 'Sieger', 'Bhkav',
            'Julia', 'Gnom', 'RRfdur', 'Vega', 'Miko', 'Dasha', 'Enzo', 'Priya', 'Kaan', 'Yuki',
            'Ravi', 'Lena', 'Tomer', 'Bruno', 'Elif', 'Nastya', 'Pablo', 'Hana', 'Igor', 'Maya',
            'Deniz', 'Sofia', 'Omar', 'Wei', 'Aylin', 'Diego', 'Noa', 'Timur', 'Chiara', 'Kenji',
        ];

        $i = 0;
        foreach ($handles as $handle) {
            $gender = ($i % 2 === 0) ? 'Female' : 'Male';
            Person::create([
                'handle'       => $handle,
                'display_name' => $handle,
                'odigo_id'     => strtolower($handle) . '@odigo.im',
                'age'          => 18 + (($i * 7) % 42),
                'gender'       => $gender,
                'region'       => $regions[$i % count($regions)],
                'language'     => $languages[$i % count($languages)],
                'occupation'   => $jobs[$i % count($jobs)],
                'topic'        => $topics[$i % count($topics)],
                'status'       => $statuses[$i % count($statuses)],
                'mood'         => $moods[$i % count($moods)],
                'intention'    => $intents[$i % count($intents)],
                'zodiac'       => $zodiacs[$i % count($zodiacs)],
                'sprite'       => $sprites[$i % count($sprites)],
                'tagline'      => $taglines[$i % count($taglines)],
                'is_friend'    => in_array($handle, ['karina', 'Lara', 'Anna', 'Bruno']),
            ]);
            $i++;
        }

        // Sample conversation history with a couple of contacts.
        $now = now();
        $seed = [
            ['peer' => 'karina', 'direction' => 'in',  'body' => 'Hey! I saw you in the Soccer room :)',          'min' => 42],
            ['peer' => 'karina', 'direction' => 'out', 'body' => 'Haha yeah, who do you support?',               'min' => 40],
            ['peer' => 'karina', 'direction' => 'in',  'body' => 'Barcelona all the way! You?',                   'min' => 38],
            ['peer' => 'karina', 'direction' => 'out', 'body' => 'Real Madrid — we can still be friends though',  'min' => 36],
            ['peer' => 'Lara',   'direction' => 'in',  'body' => 'thanks for adding me!',                          'min' => 20],
            ['peer' => 'Lara',   'direction' => 'out', 'body' => 'of course, welcome to my list',                 'min' => 18],
            ['peer' => 'Anna',   'direction' => 'in',  'body' => 'what music are you into?',                       'min' => 10],
        ];
        foreach ($seed as $m) {
            OdigoMessage::create([
                'peer'       => $m['peer'],
                'direction'  => $m['direction'],
                'type'       => 'Message',
                'body'       => $m['body'],
                'created_at' => $now->copy()->subMinutes($m['min']),
                'updated_at' => $now->copy()->subMinutes($m['min']),
            ]);
        }
    }
}
