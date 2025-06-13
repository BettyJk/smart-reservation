# Classroom Reservation System

A web-based system for reserving classroom seats, managing reservations, and handling notifications. Built with PHP, MySQL, Docker, and a modern HTML/CSS/JS frontend. Supports QR code-based seat management and an admin dashboard for user and reservation management.

## Features
- User authentication (student, teacher, admin roles)
- Reserve seats in classrooms for specific dates and times
- View and manage your reservations
- Admin dashboard for managing users and reservations
- QR code generation and scanning for seat reservations
- Notifications for reservation status
- Dockerized setup for easy deployment

## Technologies Used
- **PHP**: Backend logic and RESTful APIs
- **MySQL**: Database for users, classrooms, seats, reservations, notifications
- **Docker**: Containerization and orchestration
- **HTML/CSS/JavaScript**: Frontend UI
- **Adminer**: Database management (via Docker)
- **endroid/qr-code, khanamiryan/qrcode-detector-decoder**: QR code libraries

## Project Structure
```
db-main/
├── docker-compose.yml
├── db/
│   ├── init.sql
│   ├── insert_seats.sql
│   └── notifications_migration.sql
├── php/
│   ├── *.php (backend scripts)
│   ├── *.html (frontend pages)
│   ├── Dockerfile
│   ├── composer.json
│   └── qr_codes/ (generated QR images)
```

## Getting Started

### Prerequisites
- [Docker](https://www.docker.com/get-started)

### Setup and Run
1. Clone the repository:
   ```sh
   git clone <https://github.com/BettyJk/smart-reservation>
   cd db-main
   ```
2. Start the services:
   ```sh
   docker-compose up --build
   ```
3. Access the app:
   - PHP backend: [http://localhost:8080](http://localhost:8080)
   - Adminer (DB admin): [http://localhost:8081](http://localhost:8081)

### Default Credentials
- Register a new user or ask your admin for access.

## Database Schema
See [`db/init.sql`](db/init.sql) for full schema. Main tables:
- `users`: id, email, password_hash, role
- `classrooms`: id, name, description
- `seats`: id, classroom_id, seat_number, pos_x, pos_y
- `reservations`: id, user_id, seat_id, reservation_date, start_time, end_time, status, confirmation_code
- `notifications`: id, user_id, message, created_at

## Example Usage
- Reserve a seat: Log in, select classroom and seat, choose date/time, confirm reservation.
- Admin: Log in as admin, view/manage all reservations, register new users.
- Scan QR: Use the QR code feature to reserve a seat by scanning its code.

## Development
- PHP dependencies managed via Composer (`php/composer.json`)
- Add new features in the `php/` directory (backend or frontend)
- Database migrations/scripts in `db/`

## License
This project is for educational use at ENSAM Meknès.

## References
- [PHP](https://www.php.net/)
- [MySQL](https://www.mysql.com/)
- [Docker](https://docs.docker.com/)
- [endroid/qr-code](https://github.com/endroid/qr-code)
- [khanamiryan/qrcode-detector-decoder](https://github.com/khanamiryan/php-qrcode-detector-decoder)
