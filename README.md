# Country Encyclopedia

A web application that displays information about countries from around the world.

## Features

- Search countries by name or translation
- View detailed country information including:
  - Common and official names
  - Country code
  - Population and population rank
  - Flag
  - Area
  - Neighboring countries
  - Languages
- Mark countries as favorites
- View countries that share a language

## Technologies Used

- **Backend**: Laravel, PHP
- **Database**: PostgreSQL
- **Frontend**: Tailwind CSS
- **Containerization**: Docker

## Requirements

- Docker and Docker Compose
- Composer (for local development)
- PHP 8.2+ (for local development)

## Setup Instructions

### Using Docker (Recommended)

1. Clone the repository:

```bash
git clone https://github.com/yourusername/country-encyclopedia.git
cd country-encyclopedia
```

2. Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

3. Configure the database in the `.env` file:

```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=country_encyclopedia
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

4. Start the Docker containers:

```bash
docker-compose up -d
```

5. Install Composer dependencies:

```bash
docker-compose exec app composer install
```

6. Generate application key:

```bash
docker-compose exec app php artisan key:generate
```

7. Run migrations:

```bash
docker-compose exec app php artisan migrate
```

8. Import country data from the API:

```bash
docker-compose exec app php artisan countries:fetch
```

9. Access the application at [http://localhost:8000](http://localhost:8000)

### Local Development (Without Docker)

1. Clone the repository:

```bash
git clone https://github.com/yourusername/country-encyclopedia.git
cd country-encyclopedia
```

2. Copy the `.env.example` file to `.env` and configure your database settings:

```bash
cp .env.example .env
```

3. Install Composer dependencies:

```bash
composer install
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Run migrations:

```bash
php artisan migrate
```

6. Import country data from the API:

```bash
php artisan countries:fetch
```

7. Start the development server:

```bash
php artisan serve
```

8. Access the application at [http://localhost:8000](http://localhost:8000)

## Usage

1. Visit the homepage to search for countries
2. Click on a country to view detailed information
3. Use the heart icon to add/remove countries from your favorites
4. Click on language tags to see other countries that speak the same language
5. Click on neighboring countries to navigate to their details pages
