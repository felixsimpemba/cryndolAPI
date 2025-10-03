<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     title="Cryndol API",
 *     description="API documentation for Cryndol application",
 *     version="1.0.0"
 * )
 * 
 * @OA\Server(
 *     url="https://api.cryndol.com/v1",
 *     description="Production server"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local development server"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Business Profile",
 *     description="Business profile management endpoints"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id", type="string", example="user_123456"),
 *         @OA\Property(property="fullName", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *         @OA\Property(property="phoneNumber", type="string", example="+1234567890"),
 *         @OA\Property(property="hasBusinessProfile", type="boolean", example=true),
 *         @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     }
 * )
 * 
 * @OA\Schema(
 *     schema="BusinessProfile",
 *     type="object",
 *     properties={
 *         @OA\Property(property="id", type="string", example="business_789012"),
 *         @OA\Property(property="businessName", type="string", example="John's Consulting LLC"),
 *         @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-15T10:35:00Z"),
 *         @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-15T10:35:00Z")
 *     }
 * )
 * 
 * @OA\Schema(
 *     schema="TokenPair",
 *     type="object",
 *     properties={
 *         @OA\Property(property="accessToken", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *         @OA\Property(property="refreshToken", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *         @OA\Property(property="expiresIn", type="integer", example=3600)
 *     }
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     properties={
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Validation failed"),
 *         @OA\Property(
 *             property="errors",
 *             type="object",
 *             additionalProperties={
 *                 "type": "array",
 *                 "items": {"type": "string"}
 *             },
 *             example={"email": {"Email is already registered"}, "password": {"Password must be at least 8 characters"}}
 *         )
 *     }
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     properties={
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Error message")
 *     }
 * )
 */
class ApiDocController extends Controller
{
    // This controller is used for Swagger documentation generation
    // All OpenAPI annotations are defined above for the entire API
}
  