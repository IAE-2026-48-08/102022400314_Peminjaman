<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Loan',
    title: 'Loan',
    description: 'Model data peminjaman buku E-Library',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true, example: 1),
        new OA\Property(property: 'member_id', type: 'string', example: 'MBR-001'),
        new OA\Property(property: 'book_id', type: 'string', example: 'BOOK-123'),
        new OA\Property(property: 'book_title', type: 'string', example: 'Clean Code'),
        new OA\Property(property: 'member_name', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'loan_date', type: 'string', format: 'date', example: '2025-01-15'),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2025-01-29'),
        new OA\Property(property: 'return_date', type: 'string', format: 'date', nullable: true, example: null),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'returned', 'overdue'], example: 'active'),
        new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Peminjaman via platform'),
        new OA\Property(property: 'created_at', type: 'string', format: 'datetime', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'datetime', readOnly: true),
    ]
)]
class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'book_id',
        'book_title',
        'member_name',
        'loan_date',
        'due_date',
        'return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'loan_date'   => 'date',
        'due_date'    => 'date',
        'return_date' => 'date',
    ];
}
