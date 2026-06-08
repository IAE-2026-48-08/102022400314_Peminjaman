<?php

namespace Database\Seeders;

use App\Models\Loan;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    /**
     * Seed data contoh peminjaman buku E-Library.
     */
    public function run(): void
    {
        $loans = [
            [
                'member_id'   => 'MBR-001',
                'book_id'     => 'BOOK-001',
                'book_title'  => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'member_name' => 'Budi Santoso',
                'loan_date'   => '2025-01-10',
                'due_date'    => '2025-01-24',
                'status'      => 'returned',
                'return_date' => '2025-01-22',
                'notes'       => 'Dikembalikan lebih awal',
            ],
            [
                'member_id'   => 'MBR-002',
                'book_id'     => 'BOOK-002',
                'book_title'  => 'The Pragmatic Programmer',
                'member_name' => 'Siti Rahayu',
                'loan_date'   => '2025-01-15',
                'due_date'    => '2025-01-29',
                'status'      => 'active',
                'notes'       => 'Peminjaman e-book via platform',
            ],
            [
                'member_id'   => 'MBR-003',
                'book_id'     => 'BOOK-003',
                'book_title'  => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                'member_name' => 'Ahmad Fauzi',
                'loan_date'   => '2024-12-20',
                'due_date'    => '2025-01-03',
                'status'      => 'overdue',
                'notes'       => 'Belum dikembalikan — status overdue',
            ],
            [
                'member_id'   => 'MBR-001',
                'book_id'     => 'BOOK-004',
                'book_title'  => 'Laravel: Up & Running',
                'member_name' => 'Budi Santoso',
                'loan_date'   => '2025-01-20',
                'due_date'    => '2025-02-03',
                'status'      => 'active',
                'notes'       => null,
            ],
            [
                'member_id'   => 'MBR-004',
                'book_id'     => 'BOOK-005',
                'book_title'  => 'Domain-Driven Design',
                'member_name' => 'Dewi Lestari',
                'loan_date'   => '2025-01-18',
                'due_date'    => '2025-02-01',
                'status'      => 'active',
                'notes'       => 'Peminjaman untuk keperluan skripsi',
            ],
        ];

        foreach ($loans as $loan) {
            Loan::create($loan);
        }
    }
}
