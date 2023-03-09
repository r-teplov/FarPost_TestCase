## Требования
1. Linux
2. Запуск с локально установленным PHP
    - PHP 7.4
    - Composer
3. Запуск  Docker
    - Docker
    - Docker compose

## Запуск с локально установленным PHP
- перейти в корень проекта
- composer install
- cat path_to_file | php src/analyze.php -u 95 -t 50

## Запуск через Docker
- docker compose build
- docker compose up -d
- cat path_to_file | docker compose exec -T php php src/analyze.php -u 95 -t 50