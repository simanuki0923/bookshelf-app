<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('isbn');

        $reviews = [
            '9784101010014' => [
                ['yamada@example.com', 5, '猫の視点から人間社会を見る表現がとても面白かったです。'],
                ['suzuki@example.com', 4, '古典ですが読みやすく、ユーモアも楽しめました。'],
                ['tanaka@example.com', 3, '独特な文章なので慣れるまで少し時間がかかりました。'],
            ],

            '9784422100524' => [
                ['sato@example.com', 5, '人との接し方を改めて考えるきっかけになりました。'],
                ['takahashi@example.com', 4, '仕事にも日常生活にも活用できる内容でした。'],
                ['yamada@example.com', 5, '何度も読み返したいと思える一冊です。'],
            ],

            '9784873115658' => [
                ['suzuki@example.com', 5, 'コードを書くときに意識すべきポイントが分かりやすいです。'],
                ['tanaka@example.com', 4, '具体例が多く実務でも活用しやすい内容でした。'],
                ['sato@example.com', 5, 'プログラミング学習中の人にもおすすめできます。'],
            ],

            '9784863940246' => [
                ['takahashi@example.com', 5, '仕事だけでなく生活全体を考え直すきっかけになりました。'],
                ['yamada@example.com', 4, '内容が濃く、少しずつ読み進めるのがおすすめです。'],
                ['suzuki@example.com', 5, '実践したい考え方が多くありました。'],
            ],

            '9784101010021' => [
                ['tanaka@example.com', 4, '主人公のまっすぐな性格が印象的でした。'],
                ['sato@example.com', 5, 'テンポが良く最後まで楽しく読めました。'],
                ['takahashi@example.com', 3, '時代を感じる表現もありますが興味深い作品です。'],
            ],

            '9784309226712' => [
                ['yamada@example.com', 5, '人類史を大きな視点から理解できて非常に面白かったです。'],
                ['suzuki@example.com', 4, '歴史と科学の両面から考えられる内容でした。'],
                ['tanaka@example.com', 5, 'ボリュームがありますが読み応えがあります。'],
            ],

            '9784048930598' => [
                ['sato@example.com', 5, '保守しやすいコードについて深く考えるきっかけになりました。'],
                ['takahashi@example.com', 4, '実際の開発で役立つ考え方が多くあります。'],
                ['yamada@example.com', 5, 'エンジニアとして繰り返し読みたい一冊です。'],
            ],

            '9784478025819' => [
                ['suzuki@example.com', 5, '対話形式なので心理学の内容を理解しやすかったです。'],
                ['tanaka@example.com', 4, '人間関係について考え方が変わりました。'],
                ['sato@example.com', 4, '分かりやすく読み進めやすい内容でした。'],
            ],

            '9784163902302' => [
                ['takahashi@example.com', 4, '芸人の世界の厳しさと葛藤が印象に残りました。'],
                ['yamada@example.com', 5, '登場人物の生き方について考えさせられました。'],
                ['suzuki@example.com', 3, '独特な雰囲気のある作品でした。'],
            ],

            '9784822289607' => [
                ['tanaka@example.com', 5, '数字を使って世界を見る重要性がよく理解できました。'],
                ['sato@example.com', 5, '思い込みを見直すきっかけになる内容でした。'],
                ['takahashi@example.com', 4, 'グラフや事例が多く分かりやすかったです。'],
            ],

            '9784822251468' => [
                ['yamada@example.com', 4, '物流の変化が世界経済へ与えた影響を理解できました。'],
                ['suzuki@example.com', 5, '身近なコンテナから世界経済を考えられる面白い本でした。'],
            ],
        ];

        foreach ($reviews as $isbn => $bookReviews) {
            $book = $books->get($isbn);

            foreach ($bookReviews as [$email, $rating, $comment]) {
                Review::create([
                    'user_id' => $users->get($email)->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);
            }
        }
    }
}
