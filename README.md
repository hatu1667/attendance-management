# 勤怠管理アプリ

## 環境構築
### Dockerのビルド
1. $ cd coachtech/laravel
2. $ git@github.com:hatu1667/attendance-management.git
3. $ docker-compose up -d --build
4. $code .

### Laravel環境構築
1. $ docker-compose exec php bashでphpコンテナにログイン
2. $ composer install
3. $ cp .env.example .env
4. .envファイルを編集
```DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
5. $ php artisan key:generateでアプリケーションを実行できるようにする
6. $ php artisan migrate
7. $ php artisan db:seed

## 使用技術
* PHP 8.4.3 
* mysql 8.0.42
* Laravel 8.83.8

## URL
* 開発環境:http://localhost/
* phpMyAdmin:http://localhost:8080/

