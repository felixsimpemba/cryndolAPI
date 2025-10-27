<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Cryndol API',
    description: 'REST API for authentication, business profiles, customers, loans, and payments.'
)]
#[OA\Server(url: 'http://localhost:8000/api', description: 'Development server')]
#[OA\Server(url: 'https://api.cryndol.com/v1', description: 'Production server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
// Common schemas referenced across controllers
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Something went wrong')
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        )
    ]
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'user_123'),
        new OA\Property(property: 'fullName', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'phoneNumber', type: 'string'),
        new OA\Property(property: 'hasBusinessProfile', type: 'boolean', example: true),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
    ]
)]
#[OA\Schema(
    schema: 'BusinessProfile',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: 'business_456'),
        new OA\Property(property: 'businessName', type: 'string'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time')
    ]
)]
#[OA\Schema(
    schema: 'TokenPair',
    type: 'object',
    properties: [
        new OA\Property(property: 'accessToken', type: 'string'),
        new OA\Property(property: 'refreshToken', type: 'string'),
        new OA\Property(property: 'expiresIn', type: 'integer', example: 3600)
    ]
)]
class Docs
{
    // Empty class used only to hold top-level OpenAPI attributes/schemas
}
