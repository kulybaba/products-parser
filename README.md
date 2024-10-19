### Setup with Docker:

```shell
git clone git@github.com:kulybaba/products-parser.git
```

```shell
docker compose up -d
```

```shell
docker compose exec app sh -c 'composer install'
```

```shell
docker compose exec app sh -c 'php bin/console doctrine:migrations:migrate'
```

### Setup locally:

```shell
git clone git@github.com:kulybaba/products-parser.git
```

```shell
composer install
```

```shell
sed -i~ '/^DATABASE_HOST=/s/=.*/=127.0.0.1/' .env
```

```shell
sed -i~ '/^DATABASE_USER=/s/=.*/=admin/' .env
```

```shell
sed -i~ '/^DATABASE_PASSWORD=/s/=.*/=11111111/' .env
```

```shell
php bin/console doctrine:database:create
```

```shell
php bin/console doctrine:migrations:migrate
```

### Run console command in Docker:

```shell
docker compose exec app sh -c 'php bin/console app:parse:products'
```

### Run console command locally:

```shell
php bin/console app:parse:products
```

### API:

Start local server: `symfony server:start` or `php -S 127.0.0.1:8000 -t ./public` or `docker-compose exec app sh -c 'php -S 0.0.0.0:8000 -t ./public'`

**In Docker:**

```shell
docker compose exec app sh -c "curl --request GET -sL --url 'http://localhost:8000/api/products'"
```

**Locally:**

```shell
curl --request GET -sL --url 'http://localhost:8000/api/products'
```