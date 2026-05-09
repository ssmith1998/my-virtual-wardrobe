# Virtual Wardrobe API

A modern Symfony-based API for managing personal wardrobes with AI-powered outfit recommendations. Users can store their clothing items, upload images, and get personalized outfit suggestions using AWS AI services.

## Features

- **User Authentication**: JWT-based authentication with Google OAuth support
- **Wardrobe Management**: Create, view, and manage clothing items with image uploads
- **AI-Powered Recommendations**: Get outfit suggestions using AWS AI agents
- **Secure Image Storage**: S3-based image uploads with user-specific organization
- **RESTful API**: Clean, documented endpoints for all operations

## Tech Stack

- **Backend**: Symfony 8.0 (PHP 8.4+)
- **Database**: PostgreSQL with Doctrine ORM
- **Authentication**: JWT tokens with LexikJWTAuthenticationBundle
- **OAuth**: Google OAuth 2.0 with KnpU OAuth2 Client Bundle
- **File Storage**: AWS S3 with Flysystem
- **AI Integration**: AWS AI Agent for outfit recommendations

## Prerequisites

- PHP 8.4 or higher
- Composer
- PostgreSQL database
- AWS account with S3 and AI services
- Google OAuth credentials

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd my_virtual_wardrobe
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment Configuration**
   Copy `.env` and configure your environment variables:
   ```bash
   cp .env .env.local
   ```

   Required environment variables:
   ```env
   # Database
   DATABASE_URL="postgresql://user:password@localhost:5432/wardrobe_db"

   # JWT Keys (auto-generated)
   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem

   # AWS Configuration
   AWS_S3_BUCKET=your-s3-bucket-name
   AWS_REGION=us-east-1
   AWS_ACCESS_KEY_ID=your-aws-access-key
   AWS_SECRET_ACCESS_KEY=your-aws-secret-key
   AWS_AI_AGENT_URL=https://your-agent-endpoint.amazonaws.com/recommend
   AWS_AI_AGENT_API_KEY=your-agent-api-key

   # Google OAuth
   GOOGLE_CLIENT_ID=your-google-client-id
   GOOGLE_CLIENT_SECRET=your-google-client-secret
   ```

4. **Database Setup**
   ```bash
   # Create database
   php bin/console doctrine:database:create

   # Run migrations
   php bin/console doctrine:migrations:migrate
   ```

5. **JWT Key Generation**
   ```bash
   mkdir -p config/jwt
   openssl genrsa -out config/jwt/private.pem -aes256 4096
   openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
   ```

6. **Start the development server**
   ```bash
   php bin/console cache:clear
   php bin/console --server:start
   ```

## API Documentation

### Authentication

#### Register User
```http
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securepassword"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "username": "user@example.com",
  "password": "securepassword"
}
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

#### Google OAuth Login
```http
GET /api/auth/google
```
Redirects to Google OAuth, then returns JWT token on callback.

### Wardrobe Management

#### List Wardrobe Items
```http
GET /api/wardrobe
Authorization: Bearer {jwt-token}
```

Response:
```json
[
  {
    "id": 1,
    "name": "Blue Jeans",
    "type": "pants",
    "color": "blue",
    "season": "all",
    "imageUrl": "https://s3.amazonaws.com/bucket/user-1/wardrobe/image.jpg",
    "createdAt": "2024-01-15T10:30:00+00:00"
  }
]
```

#### Create Wardrobe Item
```http
POST /api/wardrobe
Authorization: Bearer {jwt-token}
Content-Type: application/json

{
  "name": "White T-Shirt",
  "type": "shirt",
  "color": "white",
  "season": "summer"
}
```

#### Upload Item Image
```http
POST /api/wardrobe/{id}/image
Authorization: Bearer {jwt-token}
Content-Type: multipart/form-data

image: [file]
```

### AI Recommendations

#### Get Outfit Recommendations
```http
POST /api/outfits/recommend
Authorization: Bearer {jwt-token}
Content-Type: application/json

{
  "prompt": "Suggest a casual outfit for a date",
  "season": "summer",
  "occasion": "casual"
}
```

Response:
```json
{
  "recommendations": [
    {
      "outfit_name": "Summer Casual Date",
      "items": [1, 3, 5],
      "description": "Perfect for a relaxed summer evening"
    }
  ]
}
```

## AWS Integration Setup

### S3 Bucket Configuration
1. Create an S3 bucket in your AWS account
2. Configure bucket policy for public read access (if needed)
3. Set up CORS for image uploads:
   ```json
   [
     {
       "AllowedHeaders": ["*"],
       "AllowedMethods": ["GET", "PUT", "POST"],
       "AllowedOrigins": ["*"],
       "ExposeHeaders": []
     }
   ]
   ```

### AI Agent Setup
1. Create an AWS AI Agent (Bedrock or similar)
2. Configure the agent with wardrobe/outfit recommendation prompts
3. Set up API Gateway or Lambda function for HTTP access
4. Configure authentication (API key or IAM)

## Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URIs:
   - `http://localhost:8000/api/auth/google/check` (development)
   - `https://yourdomain.com/api/auth/google/check` (production)
6. Copy Client ID and Client Secret to your `.env` file

## Development

### Running Tests
```bash
php bin/phpunit
```

### Code Quality
```bash
# PHPStan
vendor/bin/phpstan analyse

# Code style
vendor/bin/php-cs-fixer fix
```

### Database Migrations
```bash
# Create new migration
php bin/console doctrine:migrations:diff

# Run migrations
php bin/console doctrine:migrations:migrate
```

## Project Structure

```
src/
├── Controller/
│   ├── ApiController.php          # Main API endpoints
│   └── GoogleController.php       # Google OAuth handling
├── Entity/
│   ├── User.php                   # User entity
│   └── ClothingItem.php           # Wardrobe item entity
├── Repository/
│   ├── UserRepository.php         # User data access
│   └── ClothingItemRepository.php # Item data access
└── Service/
    ├── AwsAiAgentService.php      # AI recommendation service
    ├── S3UploadService.php        # File upload service
    └── WardrobeService.php        # Wardrobe business logic
```

## Security

- JWT tokens with RSA encryption
- User-scoped data isolation
- Secure file uploads with user-specific paths
- Input validation and sanitization
- CORS configuration for frontend integration

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary software.

## Support

For support, please contact the development team or create an issue in the repository.