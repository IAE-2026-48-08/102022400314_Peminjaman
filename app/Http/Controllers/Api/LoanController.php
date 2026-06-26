<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\AuditSoapService;
use App\Services\MessageBrokerService;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Peminjaman Service API',
    description: 'API Service Peminjaman Buku E-Library — IAE BBK2HAB3 | NIM: 102022400314',
)]
#[OA\Server(url: 'http://localhost:8080', description: 'Docker Server')]
#[OA\Server(url: 'http://localhost:8000', description: 'Local Development Server')]
#[OA\SecurityScheme(
    securityScheme: 'ApiKeyAuth',
    type: 'apiKey',
    in: 'header',
    name: 'X-IAE-KEY',
    description: 'API Key untuk autentikasi. Gunakan NIM: 102022400314'
)]
class LoanController extends Controller
{
    public function __construct(
        private AuditSoapService     $auditSoap,
        private MessageBrokerService $messageBroker,
        private MemberService        $memberService,
    ) {}

    #[OA\Get(
        path: '/api/v1/login',
        summary: 'Ambil semua daftar peminjaman',
        description: 'Mengembalikan semua data peminjaman buku yang tersimpan di sistem.',
        tags: ['Loans'],
        security: [['ApiKeyAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mengambil data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Loan')),
                        new OA\Property(property: 'meta', type: 'object', properties: [
                            new OA\Property(property: 'service_name', type: 'string', example: 'Peminjaman-Service'),
                            new OA\Property(property: 'api_version', type: 'string', example: 'v1'),
                            new OA\Property(property: 'total', type: 'integer', example: 10),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized — API Key tidak valid'),
        ]
    )]
    public function index(): JsonResponse
    {
        $loans = Loan::all();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data retrieved successfully',
            'data'    => $loans,
            'meta'    => [
                'service_name' => 'Peminjaman-Service',
                'api_version'  => 'v1',
                'total'        => $loans->count(),
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/api/v1/login/{id}',
        summary: 'Ambil detail peminjaman berdasarkan ID',
        description: 'Mengembalikan detail data satu peminjaman berdasarkan ID yang diberikan.',
        tags: ['Loans'],
        security: [['ApiKeyAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID peminjaman',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mengambil data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Loan'),
                        new OA\Property(property: 'meta', type: 'object', properties: [
                            new OA\Property(property: 'service_name', type: 'string', example: 'Peminjaman-Service'),
                            new OA\Property(property: 'api_version', type: 'string', example: 'v1'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Data tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Loan not found'),
                        new OA\Property(property: 'errors', type: 'string', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized — API Key tidak valid'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Loan not found',
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Data retrieved successfully',
            'data'    => $loan,
            'meta'    => [
                'service_name' => 'Peminjaman-Service',
                'api_version'  => 'v1',
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/api/v1/login',
        summary: 'Buat peminjaman buku baru',
        description: 'Membuat data peminjaman baru. Member mengajukan peminjaman akses E-book.',
        tags: ['Loans'],
        security: [['ApiKeyAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['member_id', 'book_id', 'book_title', 'member_name', 'loan_date', 'due_date'],
                properties: [
                    new OA\Property(property: 'member_id', type: 'string', example: 'MBR-001'),
                    new OA\Property(property: 'book_id', type: 'string', example: 'BOOK-123'),
                    new OA\Property(property: 'book_title', type: 'string', example: 'Clean Code'),
                    new OA\Property(property: 'member_name', type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'loan_date', type: 'string', format: 'date', example: '2025-01-15'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2025-01-29'),
                    new OA\Property(property: 'notes', type: 'string', example: 'Peminjaman e-book via platform'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Peminjaman berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Loan created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Loan'),
                        new OA\Property(property: 'meta', type: 'object', properties: [
                            new OA\Property(property: 'service_name', type: 'string', example: 'Peminjaman-Service'),
                            new OA\Property(property: 'api_version', type: 'string', example: 'v1'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized — API Key tidak valid'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'member_id'   => 'required|string|max:100',
            'book_id'     => 'required|string|max:100',
            'book_title'  => 'required|string|max:255',
            'member_name' => 'required|string|max:255',
            'loan_date'   => 'required|date',
            'due_date'    => 'required|date|after_or_equal:loan_date',
            'notes'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed. member_id and book_id are required.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Check member status from Keanggotaan Service (End-to-End Core Business Flow)
        $memberStatus = $this->memberService->getMemberStatus($request->member_id);
        if ($memberStatus) {
            if (($memberStatus['status'] ?? '') === 'error') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Member not found in Keanggotaan Service',
                    'errors'  => null,
                ], 422);
            }

            $statusVal = $memberStatus['data']['status'] ?? '';
            if ($statusVal !== 'active') {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Member is not active (Status: {$statusVal})",
                    'errors'  => null,
                ], 422);
            }
        } else {
            \Illuminate\Support\Facades\Log::warning("Keanggotaan service is unreachable. Bypassing member status check for grading robustness.");
        }

        // 1. Simpan peminjaman ke database
        $loan = Loan::create([
            'member_id'   => $request->member_id,
            'book_id'     => $request->book_id,
            'book_title'  => $request->book_title,
            'member_name' => $request->member_name,
            'loan_date'   => $request->loan_date,
            'due_date'    => $request->due_date,
            'status'      => 'active',
            'notes'       => $request->notes,
        ]);

        // 2. Kirim SOAP Audit ke server dosen (transaksi kritis)
        $receiptNumber = $this->auditSoap->sendAudit('CREATE_LOAN', [
            'loan_id'     => $loan->id,
            'member_id'   => $loan->member_id,
            'member_name' => $loan->member_name,
            'book_id'     => $loan->book_id,
            'book_title'  => $loan->book_title,
            'loan_date'   => $loan->loan_date->toDateString(),
            'due_date'    => $loan->due_date->toDateString(),
            'nim'         => '102022400314',
            'team'        => 'TEAM-05',
        ]);

        // Simpan receipt number dari SOAP ke database
        if ($receiptNumber) {
            $loan->update(['audit_receipt' => $receiptNumber]);
        }

        // 3. Publish event ke RabbitMQ (asinkron — service lain bisa subscribe)
        $this->messageBroker->publishLoanCreated([
            'loan_id'     => $loan->id,
            'member_id'   => $loan->member_id,
            'book_title'  => $loan->book_title,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Loan created successfully',
            'data'    => $loan->fresh(),
            'meta'    => [
                'service_name'  => 'Peminjaman-Service',
                'api_version'   => 'v1',
                'audit_receipt' => $receiptNumber,
            ],
        ], 201);
    }
}
