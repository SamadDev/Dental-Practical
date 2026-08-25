DB_CONNECTION=mysql
DB_HOST=your-hosting-mysql-host
DB_PORT=3306
DB_DATABASE=if0_42655436_dentail
DB_USERNAME=if0_42655436
DB_PASSWORD=your_actual_database_passwordcurl -fsSL https://raw.githubusercontent.com/SamadDev/Dental-Practical/main/backend/database/seeders/DatabaseSeeder.php \
  -o database/seeders/DatabaseSeeder.phpphp artisan tinker --execute="DB::statement(\"ALTER TABLE patients MODIFY phone VARCHAR(50) NULL\");"