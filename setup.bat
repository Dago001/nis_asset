@echo off
echo Creating NIS Asset Management System...

REM Set your XAMPP path (change if different)
set XAMPP_PATH=C:\xampp
set PROJECT_PATH=%XAMPP_PATH%\htdocs\nis-ams

REM Create project directory
mkdir %PROJECT_PATH%
cd %PROJECT_PATH%

REM Create directory structure
mkdir assets\css
mkdir assets\js
mkdir assets\images
mkdir assets\uploads
mkdir assets\uploads\land_documents
mkdir assets\uploads\building_documents
mkdir assets\uploads\rented_documents
mkdir assets\uploads\project_documents
mkdir assets\uploads\movable_documents
mkdir assets\uploads\ict_documents
mkdir assets\uploads\vehicle_documents
mkdir assets\uploads\aircraft_documents
mkdir assets\uploads\marine_documents
mkdir assets\uploads\motorcycle_documents
mkdir assets\uploads\requisitions
mkdir assets\uploads\audit_documents
mkdir assets\uploads\temp

mkdir config
mkdir core
mkdir controllers
mkdir models
mkdir views\layouts
mkdir views\auth
mkdir views\dashboard
mkdir views\users
mkdir views\land
mkdir views\buildings
mkdir views\rented
mkdir views\projects
mkdir views\movable
mkdir views\ict
mkdir views\fleet\vehicles
mkdir views\fleet\aircraft
mkdir views\fleet\marine
mkdir views\fleet\motorcycles
mkdir views\weapons
mkdir views\ammunition
mkdir views\requisitions
mkdir views\returns
mkdir views\audit\quarterly
mkdir views\reports
mkdir views\errors

mkdir api
mkdir database
mkdir database\seeders
mkdir logs
mkdir scripts
mkdir vendor

echo Project structure created successfully!
pause