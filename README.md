# Sustainability e-Commerce Project

## Overview
A simple web application that connects consumers with discounted products nearing their expiration date. Markets can list these products at a lower price, preventing waste, while consumers can purchase them at a discount.

## Features
- Two user types: Markets and Consumers
- Markets can list products approaching expiration dates with discounted prices
- Consumers can search for products, add them to cart, and purchase
- Email verification for user registration

## Setup Instructions
1. Place the files in your web server directory
2. Import the database schema from `database/schema.sql`
3. Update site and database credentials in `config/config.php`
4. Visit the website to start using the application

## Project Structure
- `/config` - Configuration files
- `/includes` - Helper functions for database, security, etc.
- `/assets` - Static assets like images and styles
- `/market` - Market user area
- `/templates` - Header and footer templates 


## Further Improvements
- Add a category to the products
- Add a filter or sorting to filter products by price
- Add a filter to filter products by category
- Add a filter or sorting to filter products by expiration date

## Notes for using Composer and PHPMailer:
1. Install Composer from https://getcomposer.org/
2. Run `composer require phpmailer/phpmailer` in project directory