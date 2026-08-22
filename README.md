# Bookshelf App

Bookshelf Appは、書籍の登録・レビュー・お気に入り・レビューいいね・ジャンル管理・ランキング・公開APIを備えた、Laravelによる書籍レビューアプリケーションです。

単に機能を実装するだけではなく、以下を意識して設計しています。

- Controllerの責務を小さくする
- 認証・認可・バリデーションの責務を分離する
- データベース制約によってデータ整合性を保証する
- Eloquentのリレーションを活用する
- N+1問題を避ける
- Feature Testによって仕様を回帰テストとして固定する
- Laravel標準の仕組みを活用し、保守しやすい構造にする

---

## 1. アプリケーション概要

ユーザーが書籍を登録し、書籍に対してレビューやお気に入り登録を行うことができます。

また、レビューへのいいね、ジャンルによる書籍分類、レビュー平均評価によるランキングを実装しています。

Web画面とは別に、書籍情報を操作するPublic APIも実装しています。

### 主な機能

- 会員登録
- ログイン
- ログアウト
- 書籍一覧
- 書籍詳細
- 書籍登録
- 書籍編集
- 書籍削除
- レビュー投稿
- レビュー編集
- レビュー削除
- お気に入り登録・解除
- お気に入り一覧
- レビューいいね登録・解除
- ジャンル登録
- ジャンル一覧
- ジャンル詳細
- ジャンル編集
- ジャンル削除
- 書籍ランキング
- Public Book API

---

## 2. 開発目的

本アプリケーションでは、Laravelを利用したWebアプリケーション開発に必要となる以下の要素を一通り実装することを目的としています。

- MVC
- 認証
- 認可
- バリデーション
- Eloquent ORM
- 1対多・多対多リレーション
- 外部キー制約
- API
- Feature Test
- コード品質管理

特に、Controllerへすべての処理を記述するのではなく、Laravelが提供する仕組みを利用して責務を適切に分離することを意識しています。

---

## 3. 使用技術

| 項目 | バージョン・用途 |
| --- | --- |
| PHP | 8.2.33 |
| Laravel | 10.50.2 |
| MySQL | 8.4 |
| Laravel Sail | Docker開発環境 |
| Laravel Fortify | 認証処理 |
| Blade | Web画面 |
| Eloquent ORM | DBアクセス・リレーション |
| FormRequest | バリデーション |
| Policy | 認可 |
| API Resource | APIレスポンス整形 |
| PHPUnit | 自動テスト |
| Laravel Pint | コードスタイル統一 |
| phpMyAdmin | DB確認 |
| Docker | 開発環境のコンテナ化 |

---

## 4. 開発環境

Laravel Sailを利用し、Docker上にPHP・MySQL・Laravelアプリケーションの実行環境を構築しています。

```text
Windows 11
  └─ WSL2 Ubuntu
       └─ Docker
            ├─ Laravel / PHP 8.2
            ├─ MySQL 8.4
            └─ phpMyAdmin
```

### Laravel Sail / Dockerを採用した理由

ローカルPCへPHPやMySQLを直接インストールする構成では、開発環境ごとの差異によって動作結果が変わる可能性があります。

Laravel SailとDockerを利用することで、以下を目的としています。

- PHPバージョンの統一
- MySQLバージョンの統一
- 開発環境の再現性確保
- ホストOSへの依存軽減
- 環境構築手順の統一

---

## 5. 環境構築

### 1. リポジトリをClone

```bash
git clone https://github.com/simanuki0923/bookshelf-app.git
cd bookshelf-app
```

### 2. Composerパッケージをインストール

```bash
composer install
```

### 3. 環境変数ファイルを作成

```bash
cp .env.example .env
```

`.env` のDatabase設定を環境に合わせて設定します。

例：

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=<DATABASE_NAME>
DB_USERNAME=<DATABASE_USER>
DB_PASSWORD=<DATABASE_PASSWORD>
```

### 4. Laravel Sailを起動

```bash
./vendor/bin/sail up -d
```

`sail` をalias登録している場合は、以下でも実行できます。

```bash
sail up -d
```

### 5. APP_KEYを生成

```bash
sail artisan key:generate
```

### 6. Migrationを実行

```bash
sail artisan migrate
```

### 7. Seederを実行

```bash
sail artisan db:seed
```

Databaseを初期化してSeederまで実行する場合は以下を使用します。

```bash
sail artisan migrate:fresh --seed
```

### アプリケーション

```text
http://localhost
```

### phpMyAdmin

```text
http://localhost:8080
```

---

## 6. データベース構成

アプリケーションの主要テーブルは以下の7テーブルです。

```text
users
books
genres
book_genre
reviews
favorites
review_likes
```

その他、Laravelや認証機能などが使用する補助テーブルがあります。

```text
password_reset_tokens
failed_jobs
personal_access_tokens
```

---

## 7. テーブル仕様

### users

ユーザー情報を管理します。

| カラム | 内容 |
| --- | --- |
| id | ユーザーID |
| name | ユーザー名 |
| email | メールアドレス |
| password | パスワード |
| email_verified_at | メール認証日時 |
| remember_token | Remember Token |
| created_at | 作成日時 |
| updated_at | 更新日時 |

`email` にはUNIQUE制約を設定しています。

---

### books

登録された書籍情報を管理します。

| カラム | 内容 |
| --- | --- |
| id | 書籍ID |
| user_id | 登録ユーザー |
| title | 書籍タイトル |
| author | 著者 |
| isbn | ISBN |
| published_date | 出版日 |
| description | 書籍説明 |
| image_url | 書籍画像URL |
| created_at | 作成日時 |
| updated_at | 更新日時 |

ISBNは13桁を想定し、重複登録を防止するため `isbn` にUNIQUE制約を設定しています。

`user_id` は書籍を登録したユーザーを表します。

---

### genres

書籍を分類するジャンルを管理します。

| カラム | 内容 |
| --- | --- |
| id | ジャンルID |
| name | ジャンル名 |

同じジャンル名の重複登録を防ぐため、`name` にUNIQUE制約を設定しています。

---

### book_genre

書籍とジャンルの多対多関係を管理する中間テーブルです。

| カラム | 内容 |
| --- | --- |
| book_id | 書籍ID |
| genre_id | ジャンルID |

`book_id + genre_id` を複合主キーとしています。

これにより、同じ書籍へ同じジャンルが重複登録されることをDBレベルで防止しています。

---

### reviews

書籍に投稿されたレビューを管理します。

| カラム | 内容 |
| --- | --- |
| id | レビューID |
| user_id | 投稿ユーザー |
| book_id | 対象書籍 |
| rating | 評価値 |
| comment | コメント |
| created_at | 作成日時 |
| updated_at | 更新日時 |

`rating` は必須で1〜5の範囲です。

`comment` も必須項目です。

#### 同一ユーザーの複数レビューについて

`reviews` では、

```text
user_id + book_id
```

にUNIQUE制約を設定していません。

これは、同一ユーザーが同じ書籍へ複数回レビューを投稿できる仕様に対応するためです。

例えば、

```text
初回読了時のレビュー
再読後のレビュー
```

のように、同じユーザーが同じ書籍について異なる時点の評価を残せる構造としています。

---

### favorites

ユーザーのお気に入り書籍を管理する中間テーブルです。

| カラム | 内容 |
| --- | --- |
| user_id | ユーザーID |
| book_id | 書籍ID |

`user_id + book_id` を複合主キーとしています。

同一ユーザーによる同一書籍への重複お気に入りをDBレベルで防止しています。

---

### review_likes

レビューに対するいいねを管理する中間テーブルです。

| カラム | 内容 |
| --- | --- |
| user_id | いいねしたユーザー |
| review_id | 対象レビュー |

`user_id + review_id` を複合主キーとしています。

同一ユーザーによる同一レビューへの重複いいねをDBレベルで防止しています。

---

## 8. Eloquentリレーション設計

モデル間の関連はEloquent ORMで定義しています。

### User

```text
User
├─ hasMany Books
├─ hasMany Reviews
├─ belongsToMany FavoriteBooks
└─ belongsToMany LikedReviews
```

### Book

```text
Book
├─ belongsTo User
├─ belongsToMany Genres
├─ hasMany Reviews
└─ belongsToMany FavoritedByUsers
```

### Genre

```text
Genre
└─ belongsToMany Books
```

### Review

```text
Review
├─ belongsTo User
├─ belongsTo Book
└─ belongsToMany LikedByUsers
```

### Eloquentを採用した理由

ControllerでSQLやJOINを直接記述するのではなく、Modelへリレーションを定義することで以下を目的としています。

- データ構造をModelから把握しやすくする
- ControllerのDB処理を簡潔にする
- Laravel標準の記述方法へ統一する
- 関連データ取得を再利用しやすくする
- モデル間の責務を明確にする

---

## 9. 認証・認可設計

### 認証

認証にはLaravel Fortifyを使用しています。

Basic機能として以下を実装しています。

- 会員登録
- ログイン
- ログアウト

### Fortifyを採用した理由

認証を独自実装すると、Password Hash、Session、CSRF、ログイン状態など、多くのセキュリティ要素を個別に考慮する必要があります。

Laravel Fortifyを利用することで、Laravel標準の認証機構を使用しつつ、Bladeによる画面表示と認証処理の責務を分離しています。

これにより、認証処理の安全性・保守性を高めています。

### 認可

書籍とレビューの編集・削除にはLaravel Policyを使用しています。

```text
BookPolicy
ReviewPolicy
```

#### BookPolicy

書籍の編集・削除は、書籍登録者本人のみ許可します。

#### ReviewPolicy

レビューの編集・削除は、レビュー投稿者本人のみ許可します。

### Policyを採用した理由

Controllerへ以下のような認可判定を繰り返し記述すると、Controllerの責務が増え、認可処理が複数箇所へ分散します。

```php
if ($book->user_id !== auth()->id()) {
    abort(403);
}
```

Policyへ認可処理を分離することで、以下を目的としています。

- Controllerを簡潔に保つ
- 認可ルールを一箇所へ集約する
- 認可仕様変更時の修正範囲を減らす
- 不正な編集・削除を防止する

---

## 10. バリデーション設計

入力値のValidationにはLaravel FormRequestを使用しています。

主なRequestクラスは以下です。

```text
StoreBookRequest
UpdateBookRequest

StoreReviewRequest
UpdateReviewRequest

StoreGenreRequest
UpdateGenreRequest

Api/V1/ListBooksRequest
Api/V1/StoreBookRequest
Api/V1/UpdateBookRequest
```

### FormRequestを採用した理由

Controller内へValidationを直接記述すると、以下のような複数の責務がControllerへ集中します。

```text
認可
Validation
DB更新
画面遷移
```

ValidationをFormRequestへ分離することで、Controllerを処理の組み立てとレスポンス制御へ集中させています。

---

## 11. 各機能の設計背景

### 書籍機能

書籍には登録ユーザーを示す `user_id` を保持しています。

```text
登録ユーザー本人
├─ 編集可能
└─ 削除可能

その他のユーザー
├─ 編集不可
└─ 削除不可
```

書籍一覧は新しい書籍から表示し、10件単位でPaginationしています。

---

### レビュー機能

レビューには以下を保持しています。

```text
user_id
book_id
rating
comment
```

`rating` は1〜5、`comment` は必須です。

同一ユーザーが同一書籍へ複数レビューを投稿できる仕様のため、

```text
user_id + book_id
```

にはUNIQUE制約を設定していません。

#### この設計を採用した理由

レビューを「ユーザーと書籍の関係を1件だけ保存するデータ」ではなく、「ある時点でユーザーが投稿した評価データ」として扱っています。

そのため、同じ書籍を再読した際などにも新しいレビューを投稿できます。

---

### お気に入り機能

ユーザーと書籍は多対多関係になるため、`favorites` 中間テーブルを利用しています。

`user_id + book_id` を複合主キーにすることで、アプリケーション側だけでなくDatabase側でも重複登録を防止しています。

---

### レビューいいね機能

レビューいいねには `review_likes` 中間テーブルを使用しています。

```text
User
  ↓
review_likes
  ↓
Review
```

登録・解除にはEloquentの `toggle()` を利用しています。

```text
未登録
  ↓
いいね登録

登録済み
  ↓
いいね解除
```

同一エンドポイントで登録と解除を切り替えられる構造です。

#### 自分のレビューへのいいね

レビュー投稿者本人も自分のレビューへいいねできます。

レビュー投稿者と、レビューへいいねするユーザーを別の役割として扱っているため、

```text
review.user_id === login user
```

による制限を設けていません。

---

### ジャンル機能

BookとGenreは多対多関係です。

```text
Book
  ↓
book_genre
  ↓
Genre
```

これにより、

```text
1冊の書籍 → 複数ジャンル
1ジャンル → 複数書籍
```

を表現しています。

書籍から利用されているGenreは削除できないよう制御しています。

---

### ランキング機能

レビュー平均評価が高い書籍から順番に最大10件表示します。

レビューが存在しない書籍はランキング対象外です。

平均評価やレビュー件数には、Eloquentの以下の集計機能を利用しています。

```text
withAvg()
withCount()
```

PHP側ですべてのReviewを取得して計算するのではなく、Database側で集計することで、不要なデータ取得を減らしています。

---

## 12. データ整合性設計

外部キー制約とCascade / Restrictを利用し、削除処理後に不整合データが残らないよう設計しています。

### Book削除時

```text
Book
├─ Reviews       → 削除
├─ Favorites     → 削除
├─ book_genre    → 関連削除
└─ Genres        → 削除しない
```

### Review削除時

```text
Review
└─ ReviewLikes → 削除
```

### cascadeOnDeleteを採用した理由

例えば書籍削除後にその書籍を参照するReviewが残ると、存在しない書籍IDを参照する孤立データになります。

書籍に依存して存在するデータには `cascadeOnDelete()` を設定し、親データ削除時に関連データも削除されるようにしています。

### Genreを削除しない理由

Genreは特定の書籍専用のデータではなく、複数の書籍から共有されるマスタデータです。

そのためBook削除時にはGenre本体を削除せず、`book_genre` の関連だけを削除します。

また、利用中Genreの誤削除を防ぐため、`book_genre.genre_id` 側には削除制限を設定しています。

---

## 13. パフォーマンス設計

### N+1問題への対応

関連データを表示する処理ではEager Loadingを利用しています。

BookごとにGenreやReviewを個別取得すると、Book件数に応じてSQL発行回数が増加するN+1問題が発生します。

そのため、以下を利用しています。

```text
with()
withAvg()
withCount()
```

必要な関連情報を事前にまとめて取得することで、SQL発行回数を抑えています。

### Pagination

以下の一覧では10件単位のPaginationを使用しています。

- 書籍一覧
- お気に入り一覧
- ジャンル別書籍一覧

全件を一度に取得せず表示件数を制限することで、データ量増加時のDB取得量・メモリ使用量・HTML描画量の増加を抑えることを目的としています。

---

## 14. Public API

Web画面とは別にBook情報を扱うPublic APIを実装しています。

### エンドポイント

| Method | URI | 内容 |
| --- | --- | --- |
| GET | `/api/v1/books` | 書籍一覧 |
| POST | `/api/v1/books` | 書籍登録 |
| GET | `/api/v1/books/{book}` | 書籍詳細 |
| PUT | `/api/v1/books/{book}` | 書籍更新 |
| DELETE | `/api/v1/books/{book}` | 書籍削除 |

### API Controllerを分離した理由

Web用ControllerとAPI用Controllerでは返却する内容が異なります。

```text
Web
→ Blade Viewを返す

API
→ JSONを返す
```

そのため、API Controllerを以下へ分離しています。

```text
App\Http\Controllers\Api\V1
```

### V1名前空間を採用した理由

将来的にAPI仕様変更が発生した場合でも、

```text
/api/v1/
/api/v2/
```

のようにVersionを分離できる構成にするためです。

---

## 15. API Resource

APIレスポンスにはLaravel API Resourceを使用しています。

```text
BookResource
ReviewResource
```

### API Resourceを採用した理由

Eloquent ModelをそのままJSONとして返すと、Database内部構造とAPI仕様が強く結合します。

API Resourceを利用することで、

- APIとして公開する項目を明確にする
- Model変更によるAPIへの影響を減らす
- JSON構造を統一する
- APIレスポンス生成をControllerから分離する

ことを目的としています。

---

## 16. Controllerの責務

本アプリケーションでは以下のように責務を分離しています。

```text
Controller
→ 処理の組み立て・レスポンス

FormRequest
→ Validation

Policy
→ Authorization

Model
→ Relation / Data Access

API Resource
→ API Response
```

Controllerへ処理を集中させず、それぞれの役割をLaravelの標準機能へ分離することで保守しやすい構成を目指しています。

---

## 17. テスト方針

Feature Testを中心に、単純な正常系だけでなく以下を確認しています。

- Authentication
- Authorization
- Validation
- Database
- Relation
- Cascade Delete
- 404
- Guest access
- API

### 主なテスト

```text
AuthenticationTest
BasicWebTest

BookCreateTest
BookIndexTest
BookShowTest
BookUpdateTest
BookDeleteTest

ReviewCreateTest
ReviewUpdateTest
ReviewDeleteTest
ReviewLikeTest

FavoriteToggleTest
FavoriteIndexTest

GenreCreateTest
GenreIndexTest
GenreShowTest
GenreUpdateTest
GenreDeleteTest

RankingTest

Api/BookApiTest
```

### 仕様をFeature Testとして固定

重要な仕様はFeature Testとして残しています。

#### 同一ユーザーが同一書籍へ複数レビューできる

```text
same user can create multiple reviews for same book
```

#### 自分自身のレビューへいいねできる

```text
user can like own review
```

#### Book削除時の関連データ

```text
reviews are deleted with book
favorites are deleted with book
book genre relationships are deleted with book
genres are not deleted with book
```

仕様をテストとして固定することで、将来実装を変更した際にも既存仕様が壊れていないことを確認できます。

### 現在のテスト結果

```text
Tests:      166 passed
Assertions: 505
Coverage:   92.0%
```

### テスト実行

```bash
sail artisan test
```

### Coverage確認

```bash
sail artisan test --coverage
```

---

## 18. コード品質

Laravel Pintを利用してLaravelのコーディングスタイルへ統一しています。

### コードスタイル確認

```bash
sail bin pint --test
```

### 自動修正

```bash
sail bin pint
```

### Pintを採用した理由

コードスタイルを自動で統一することで、

- import順
- インデント
- 空行
- 波括弧位置
- コーディング規約

などの差異を減らし、コードレビュー時にロジックへ集中しやすくすることを目的としています。

---

## 19. Migration設計

Migrationの状態は以下で確認できます。

```bash
sail artisan migrate:status
```

レビューコメントについては、初期Migration作成後に必須仕様が確定したため、後続Migrationで `NOT NULL` へ変更しています。

```text
make_comment_required_on_reviews_table
```

### 後続Migrationを採用した理由

既に適用済みのMigrationを書き換えるだけでは、Migration済みのDatabaseへ変更が反映されません。

Schema変更を新しいMigrationとして追加することで、

- DB変更履歴を残す
- 既存環境にも変更を適用する
- 変更内容をGit上で追跡する

ことができます。

---

## 20. 主なルーティング

### Public

```text
GET /
GET /books/{book}
GET /ranking
```

### Authentication

```text
GET  /register
POST /register

GET  /login
POST /login

POST /logout
```

### Books

```text
GET    /books/create
POST   /books
GET    /books/{book}
GET    /books/{book}/edit
PUT    /books/{book}
DELETE /books/{book}
```

### Reviews

```text
POST   /books/{book}/reviews
GET    /reviews/{review}/edit
PUT    /reviews/{review}
DELETE /reviews/{review}
```

### Favorites

```text
GET  /favorites
POST /books/{book}/favorites
```

### Review Likes

```text
POST /reviews/{review}/like
```

### Genres

```text
GET    /genres
GET    /genres/create
POST   /genres
GET    /genres/{genre}
GET    /genres/{genre}/edit
PUT    /genres/{genre}
DELETE /genres/{genre}
```

---

## 21. 開発時の確認コマンド

### PHPバージョン

```bash
sail php -v
```

### Laravelバージョン

```bash
sail artisan --version
```

### Composer依存関係

```bash
sail composer check-platform-reqs
```

### Migration

```bash
sail artisan migrate:status
```

### Route

```bash
sail artisan route:list
```

### Pint

```bash
sail bin pint --test
```

### Test

```bash
sail artisan test
```

### Coverage

```bash
sail artisan test --coverage
```

---

## 22. 設計上意識したポイント

### 責務を分離する

```text
Controller
→ Application処理

FormRequest
→ Validation

Policy
→ Authorization

Model
→ Relation / Data Access

API Resource
→ API Response
```

### DBで保証できるものはDBでも保証する

以下はApplication側だけでなく、Database制約でも重複を防止しています。

```text
ISBN
お気に入り
レビューいいね
書籍×ジャンル
```

### 関連データの整合性を保証する

外部キー制約とCascade / Restrictを利用し、削除後に不整合データが残らない構造にしています。

### パフォーマンスを意識する

```text
Eager Loading
Pagination
DB集計
```

を利用し、データ量増加時にも無駄な取得処理を増やさないよう意識しています。

### 仕様をテストとして残す

重要な仕様をFeature Testとして実装することで、将来コード変更を行った場合でも既存機能が壊れていないことを自動で確認できる構成としています。

---

## 23. まとめ

Bookshelf Appでは、単純にCRUDを実装するだけではなく、Laravelの各機能を役割ごとに分けて利用しています。

```text
Fortify
→ Authentication

Policy
→ Authorization

FormRequest
→ Validation

Eloquent ORM
→ Relation / Data Access

API Resource
→ API Response

Migration / Foreign Key
→ Database Integrity

Eager Loading
→ N+1対策

Pagination
→ 大量データ対策

PHPUnit
→ Regression Test

Laravel Pint
→ Code Quality
```

機能が動作することだけでなく、

**「なぜその実装方法を選択したのか」**

を意識し、保守性・データ整合性・パフォーマンス・テスト容易性を考慮した設計を行っています。