# Cryndol API Documentation

## Overview

The Cryndol API provides authentication and business profile management services. This API follows RESTful principles and uses JWT tokens for authentication.

## Base URL

- **Production**: `https://api.cryndol.com/v1`
- **Development**: `http://localhost:8000/api`

## Authentication

All endpoints (except registration and login) require authentication using Bearer token in the Authorization header:

```
Authorization: Bearer <jwt_token>
```

## Rate Limiting

- **Registration**: 5 attempts per hour per IP
- **Login**: 10 attempts per hour per IP
- **General API**: 1000 requests per hour per user

## Endpoints

### 1. Authentication Endpoints

#### 1.1 Register Personal Profile
**POST** `/auth/register/personal`

Creates a new user account with personal information.

**Request Body:**
```json
{
  "fullName": "John Doe",
  "email": "john.doe@example.com",
  "phoneNumber": "+1234567890",
  "password": "SecurePassword123!",
  "acceptTerms": true
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Personal profile created successfully",
  "data": {
    "user": {
      "id": "user_123456",
      "fullName": "John Doe",
      "email": "john.doe@example.com",
      "phoneNumber": "+1234567890",
      "createdAt": "2024-01-15T10:30:00Z",
      "updatedAt": "2024-01-15T10:30:00Z"
    },
    "tokens": {
      "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "expiresIn": 3600
    }
  }
}
```

#### 1.2 Login
**POST** `/auth/login`

Authenticates user and returns access tokens.

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "password": "SecurePassword123!"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": "user_123456",
      "fullName": "John Doe",
      "email": "john.doe@example.com",
      "phoneNumber": "+1234567890",
      "hasBusinessProfile": true,
      "createdAt": "2024-01-15T10:30:00Z",
      "updatedAt": "2024-01-15T10:30:00Z"
    },
    "tokens": {
      "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "expiresIn": 3600
    }
  }
}
```

#### 1.3 Refresh Token
**POST** `/auth/refresh`

Refreshes access token using refresh token.

**Request Body:**
```json
{
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expiresIn": 3600
  }
}
```

#### 1.4 Logout
**POST** `/auth/logout`

Invalidates user tokens and logs out user.

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

#### 1.5 Get User Profile
**GET** `/auth/profile`

Retrieves current user profile information.

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "user_123456",
      "fullName": "John Doe",
      "email": "john.doe@example.com",
      "phoneNumber": "+1234567890",
      "hasBusinessProfile": true,
      "createdAt": "2024-01-15T10:30:00Z",
      "updatedAt": "2024-01-15T10:30:00Z"
    },
    "businessProfile": {
      "id": "business_789012",
      "businessName": "John's Consulting LLC",
      "createdAt": "2024-01-15T10:35:00Z",
      "updatedAt": "2024-01-15T10:35:00Z"
    }
  }
}
```

### 2. Business Profile Endpoints

#### 2.1 Create Business Profile
**POST** `/auth/business-profile`

Creates or updates business profile for authenticated user.

**Headers:**
```
Authorization: Bearer <access_token>
```

**Request Body:**
```json
{
  "businessName": "John's Consulting LLC"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Business profile created successfully",
  "data": {
    "businessProfile": {
      "id": "business_789012",
      "businessName": "John's Consulting LLC",
      "createdAt": "2024-01-15T10:35:00Z",
      "updatedAt": "2024-01-15T10:35:00Z"
    }
  }
}
```

#### 2.2 Update Business Profile
**PUT** `/auth/business-profile`

Updates business profile for authenticated user.

**Headers:**
```
Authorization: Bearer <access_token>
```

**Request Body:**
```json
{
  "businessName": "Updated Business Name"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Business profile updated successfully",
  "data": {
    "businessProfile": {
      "id": "business_789012",
      "businessName": "Updated Business Name",
      "createdAt": "2024-01-15T10:35:00Z",
      "updatedAt": "2024-01-15T10:36:00Z"
    }
  }
}
```

#### 2.3 Delete Business Profile
**DELETE** `/auth/business-profile`

Deletes business profile for authenticated user.

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Business profile deleted successfully"
}
```

## Error Responses

### Common Error Formats

**400 Bad Request**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "fieldName": ["Error message 1", "Error message 2"]
  }
}
```

**401 Unauthorized**
```json
{
  "success": false,
  "message": "Authentication required"
}
```

**403 Forbidden**
```json
{
  "success": false,
  "message": "Access denied"
}
```

**404 Not Found**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**429 Too Many Requests**
```json
{
  "success": false,
  "message": "Rate limit exceeded",
  "retryAfter": 60
}
```

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Internal server error"
}
```

## Data Models

### User Model
```typescript
interface User {
  id: string;
  fullName: string;
  email: string;
  phoneNumber: string;
  hasBusinessProfile: boolean;
  createdAt: string; // ISO 8601 datetime
  updatedAt: string; // ISO 8601 datetime
}
```

### BusinessProfile Model
```typescript
interface BusinessProfile {
  id: string;
  businessName: string;
  createdAt: string; // ISO 8601 datetime
  updatedAt: string; // ISO 8601 datetime
}
```

### Token Model
```typescript
interface TokenPair {
  accessToken: string;
  refreshToken: string;
  expiresIn: number; // seconds
}
```

## Security Considerations

### Password Requirements
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

### Token Security
- Access tokens expire in 1 hour
- Refresh tokens expire in 30 days
- Tokens are stored securely and cannot be reused after logout

## Testing

### Running the Test Script
```bash
php test_api.php
```

### Swagger Documentation
Visit `http://localhost:8000/api/documentation` for interactive API documentation.

## Installation & Setup

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Laravel 12+

### Installation Steps
1. Clone the repository
2. Install dependencies: `composer install`
3. Copy environment file: `cp .env.example .env`
4. Generate application key: `php artisan key:generate`
5. Configure database in `.env`
6. Run migrations: `php artisan migrate`
7. Start development server: `php artisan serve`

### Environment Variables
```env
APP_NAME=Cryndol API
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cryndol_api
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

## API Versioning

This is version 1.0.0 of the Cryndol API. Future versions will maintain backward compatibility where possible.

## Support

For API support or questions, please contact the development team.
