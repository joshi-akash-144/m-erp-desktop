# 🧾 ERP System - Version 2.0.0

A modular ERP system built with **Laravel 11**, **Vite**, and **MySQL**, designed for multi-company management and role-based access control.

---

```bash
# 1️⃣ Clone the repository
git clone https://gitlab.com/your-username/erp-v2.git
cd erp-v2

# 2️⃣ Install dependencies
composer install
npm install

# 3️⃣ Copy environment file
cp .env.example .env

# 4️⃣ Generate app key
php artisan key:generate

# 5️⃣ Set valid Cache Path
mkdir -p storage/framework/{sessions,views,cache}

# 6️⃣ Set up database
# Edit your .env file:

# If using MySQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp
DB_USERNAME=root
DB_PASSWORD=""

# Then run migrations with seed data
php artisan migrate --seed

# 7️⃣ Run the application
php artisan serve
npm run dev

# Open your browser at: http://127.0.0.1:8000

# 🏷 Versioning
git tag -a v2.0.0 -m "Initial ERP v2 stable release"
git push origin v2.0.0

# 🧰 Useful Commands
# Run Laravel Server: php artisan serve
# Compile Assets: npm run dev
# Compile for Production: npm run build
# Run Migrations: php artisan migrate
# Clear Cache: php artisan optimize:clear

# 📄 License
# This project is licensed under the MIT License
