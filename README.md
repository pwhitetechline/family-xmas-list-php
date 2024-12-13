# Family Christmas List Application

A web application that helps families organize and manage their Christmas wishlists, making holiday gift-giving easier and more organized.

## Features

- **User Management**
  - Register and login securely
  - Manage your personal profile

- **Wishlists**
  - Create personal wishlists
  - Add, edit, and remove items from wishlists
  - Mark items as purchased (without revealing to the wishlist owner)
  - View wishlists from family members

- **Family Groups**
  - Create family groups
  - Invite family members via email
  - Share wishlists within family groups
  - Manage group memberships

## Installation Requirements

1. **XAMPP**
   - Apache web server
   - MySQL database
   - PHP 7.4 or higher

## Setup Instructions

1. **Install XAMPP**
   - Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start Apache and MySQL services

2. **Database Setup**
   - Navigate to `http://localhost/phpmyadmin`
   - Create a new database named `family_christmas_list`
   - Import the database schema from `database/setup.php`

3. **Application Setup**
   - Clone or download this repository to your XAMPP's `htdocs` folder
   - The application should be accessible at `http://localhost/family-christmas-list`

## Usage Guide

### 1. Getting Started
- Register for a new account at `/auth/register`
- Log in with your credentials
- Create your first wishlist

### 2. Managing Wishlists
- Create multiple wishlists for different occasions
- Add items with details like name, price, and links
- Edit or delete items as needed
- Mark items as purchased when buying for others

### 3. Family Groups
- Create a new family group
- Invite members using their email address
- Accept invitations through email links
- View and interact with family members' wishlists

### 4. Privacy and Security
- Only logged-in users can access wishlists
- Users can only see wishlists they have permission to view
- Purchase status is hidden from wishlist owners

## Contributing

Feel free to submit issues and enhancement requests!

## License

This project is open source and available under the MIT License.

## Support

For support or questions, please open an issue in the repository.
