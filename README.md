# Country Encyclopedia

A modern web application that displays information about countries from around the world using REST Countries API. This application is built as a Single Page Application (SPA) with a Vue.js frontend and Laravel backend.

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
- Responsive design for mobile and desktop devices

## Technologies Used

- **Backend**: Laravel 10, PHP 8.2+
- **Database**: SQLite (only for storing favorites)
- **API**: REST Countries API for country data
- **Frontend**: Vue.js 3 with Composition API, Pinia for state management
- **Styling**: Tailwind CSS, with dark mode support
- **Routing**: Vue Router for client-side routing
- **Containerization**: Docker

## Requirements

- Docker and Docker Compose (for containerized setup)
- PHP 8.2+ (for local development)
- Node.js 18+ (for frontend development)

## Setup Instructions

### Using Docker (Recommended)

1. Clone the repository:

```bash
git clone https://github.com/MikelisPZX/tet.encyclopedia.git
cd tet.encyclopedia
```

2. Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

3. Configure the database in the `.env` file (already set to use SQLite):

```
DB_CONNECTION=sqlite
```

4. Start the Docker containers:

```bash
docker-compose up -d
```

5. Install dependencies and set up the application:

```bash
# Install Composer dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Create SQLite database file
docker-compose exec app touch database/database.sqlite

# Run migrations
docker-compose exec app php artisan migrate

# Install Node.js dependencies and build frontend assets
docker-compose exec app npm install
docker-compose exec app npm run build
```

6. Access the application at [http://localhost:80](http://localhost:80)

### Local Development

1. Clone the repository:

```bash
git clone https://github.com/MikelisPZX/tet.encyclopedia.git
cd tet.encyclopedia
```

2. Copy the `.env.example` file to `.env`:

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

5. Create SQLite database file:

```bash
touch database/database.sqlite
```

6. Run migrations:

```bash
php artisan migrate
```

7. Install Node.js dependencies:

```bash
npm install
```

8. Start the development servers:

```bash
# In one terminal window, start the PHP server
php artisan serve

# In another terminal window, start the Vite dev server
npm run dev
```

9. Access the application at [http://localhost:8000](http://localhost:8000)

## Usage

1. Use the search bar to find countries by name (works with translations too, e.g., "Германия" will find Germany)
2. Click on a country to view detailed information
3. Use the heart icon to add/remove countries from your favorites
4. Navigate to the "Favorite Countries" section to see your favorites
5. Click on language tags to see other countries that speak the same language
6. Click on neighboring countries to navigate to their details pages

## Architecture

The application uses a SPA architecture with Vue.js on the frontend and Laravel as a backend API. Key components:

- Vue.js 3 with Composition API for reactive components
- Pinia store for state management
- Vue Router for client-side routing
- Laravel backend providing RESTful API endpoints
- SQLite database for storing user favorites

