# Cryndol API Documentation

## Overview

The Cryndol API provides authentication, business profile management, customers, loans, loan payments, and dashboard summary services. This API follows RESTful principles and uses Sanctum bearer tokens for authentication.

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

### 3. Dashboard Endpoints

#### 3.1 Business Summary
**GET** `/dashboard/summary/{businessId}`

Returns aggregated metrics for the specified business.

- Path params: `businessId` (string | number)
- Auth: Bearer token required

**Response (200 OK) Example:**
```json
{
  "success": true,
  "data": {
    "totals": {
      "loans": 120,
      "customers": 85,
      "activeLoans": 64,
      "pendingLoans": 8
    },
    "recentPayments": [
      { "loanId": 12, "amount": 250.00, "date": "2025-10-10" }
    ]
  }
}
```

### 4. Customers Endpoints

Base path: `/customers` (all require Bearer token)

- Model fields used in this API: `fullName`, `email`, `phoneNumber`.

#### 4.1 List Customers
**GET** `/customers`

Query params (optional):
- `page`, `perPage`, search filters may be supported depending on implementation.

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "customers": [
      { "id": 1, "fullName": "Alice", "email": "alice@example.com", "phoneNumber": "+111" }
    ],
    "pagination": { "page": 1, "perPage": 15, "total": 1 }
  }
}
```

#### 4.2 Create Customer
**POST** `/customers`

**Request Body:**
```json
{
  "fullName": "Alice Smith",
  "email": "alice@example.com",
  "phoneNumber": "+111"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Customer created",
  "data": { "customer": { "id": 2, "fullName": "Alice Smith", "email": "alice@example.com", "phoneNumber": "+111" } }
}
```

#### 4.3 Get Customer By ID
**GET** `/customers/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": { "customer": { "id": 2, "fullName": "Alice Smith", "email": "alice@example.com", "phoneNumber": "+111" } }
}
```

#### 4.4 Update Customer
**PUT** `/customers/{id}`

**Request Body:**
```json
{
  "fullName": "Alice S.",
  "email": "alice@example.com",
  "phoneNumber": "+222"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Customer updated",
  "data": { "customer": { "id": 2, "fullName": "Alice S.", "email": "alice@example.com", "phoneNumber": "+222" } }
}
```

#### 4.5 Delete Customer
**DELETE** `/customers/{id}`

**Response (200 OK):**
```json
{ "success": true, "message": "Customer deleted" }
```

### 5. Loans Endpoints

Base path: `/loans` (all require Bearer token)

- Model fields (per current code): `user_id`, `borrower_id`, `principal`, `interestRate`, `termMonths`, `startDate`, `status`, `totalPaid`.

#### 5.1 List Loans
**GET** `/loans`

Optional query params may include filters (status, borrowerId, pagination).

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "loans": [
      {
        "id": 10,
        "user_id": 1,
        "borrower_id": 2,
        "principal": "10000.00",
        "interestRate": "12.00",
        "termMonths": 12,
        "startDate": "2025-01-01",
        "status": "ACTIVE",
        "totalPaid": "1200.00"
      }
    ],
    "pagination": { "page": 1, "perPage": 15, "total": 1 }
  }
}
```

#### 5.2 Create Loan
**POST** `/loans`

**Request Body:**
```json
{
  "borrower_id": 2,
  "principal": 10000,
  "interestRate": 12,
  "termMonths": 12,
  "startDate": "2025-01-01"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Loan created",
  "data": {
    "loan": {
      "id": 11,
      "user_id": 1,
      "borrower_id": 2,
      "principal": "10000.00",
      "interestRate": "12.00",
      "termMonths": 12,
      "startDate": "2025-01-01",
      "status": "PENDING",
      "totalPaid": "0.00"
    }
  }
}
```

#### 5.3 Get Loan By ID
**GET** `/loans/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": { "loan": { "id": 11, "borrower_id": 2, "principal": "10000.00", "interestRate": "12.00", "termMonths": 12, "startDate": "2025-01-01", "status": "PENDING", "totalPaid": "0.00" } }
}
```

#### 5.4 Update Loan
**PUT** `/loans/{id}`

**Request Body:**
```json
{
  "principal": 12000,
  "interestRate": 11.5,
  "termMonths": 10,
  "startDate": "2025-02-01",
  "status": "APPROVED"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Loan updated",
  "data": { "loan": { "id": 11 } }
}
```

#### 5.5 Delete Loan
**DELETE** `/loans/{id}`

**Response (200 OK):**
```json
{ "success": true, "message": "Loan deleted" }
```

#### 5.6 Change Loan Status
**POST** `/loans/{id}/status`

**Request Body:**
```json
{
  "status": "APPROVED"
}
```

Accepted values typically include: `PENDING`, `APPROVED`, `ACTIVE`, `PAID`, `DEFAULTED`, `CANCELLED`.

**Response (200 OK):**
```json
{ "success": true, "message": "Status updated", "data": { "loanId": 11, "status": "APPROVED" } }
```

### 6. Loan Payments Endpoints

#### 6.1 Add Payment To Loan
**POST** `/loans/{id}/payments`

Adds a payment record to the specified loan.

**Request Body:**
```json
{
  "payment_date": "2025-10-10",
  "amount_paid": 250.00,
  "principal_paid": 200.00,
  "interest_paid": 50.00,
  "payment_method": "BANK_TRANSFER",
  "reference_number": "REF-12345",
  "notes": "October installment"
}
```

Note: The backend calculates `balance_remaining` and updates `totalPaid` accordingly if implemented in controller.

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Payment recorded",
  "data": {
    "payment": {
      "id": "uuid",
      "loan_id": 11,
      "payment_date": "2025-10-10",
      "amount_paid": "250.00",
      "principal_paid": "200.00",
      "interest_paid": "50.00",
      "balance_remaining": "9800.00",
      "payment_method": "BANK_TRANSFER",
      "reference_number": "REF-12345",
      "notes": "October installment"
    }
  }
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

### Customer Model (Borrower)
```typescript
interface Customer {
  id: number;
  fullName: string;
  email: string;
  phoneNumber: string;
}
```

### Loan Model
```typescript
interface Loan {
  id: number;
  user_id: number;
  borrower_id: number;
  principal: string; // decimal
  interestRate: string; // decimal percentage
  termMonths: number;
  startDate: string; // date (YYYY-MM-DD)
  status: 'PENDING' | 'APPROVED' | 'ACTIVE' | 'PAID' | 'DEFAULTED' | 'CANCELLED';
  totalPaid: string; // decimal
}
```

### LoanPayment Model
```typescript
interface LoanPayment {
  id: string; // uuid
  loan_id: number;
  payment_date: string; // date
  amount_paid: string; // decimal
  principal_paid: string; // decimal
  interest_paid: string; // decimal
  balance_remaining: string; // decimal
  payment_method: 'BANK_TRANSFER' | 'CASH' | 'CHEQUE' | 'MOBILE_MONEY';
  reference_number?: string;
  notes?: string;
}
```

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
