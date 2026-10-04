<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminReportService
{
    private const REPORTS = [
        'customers' => 'Customer',
        'motor_interest' => 'Motor Interest',
        'credit_applications' => 'Credit Application',
        'dana_tunai' => 'Dana Tunai',
        'chat_activity' => 'Chat Activity',
        'documents' => 'Documents',
        'customer_growth' => 'Customer Growth',
        'application_status' => 'Application Status',
        'motor_interest_summary' => 'Motor Interest Summary',
    ];

    private const COLUMNS = [
        'customers' => [
            'customer_id' => 'ID',
            'customer_name' => 'Nama',
            'phone' => 'No Telepon',
            'region' => 'Wilayah',
            'district' => 'Kecamatan',
            'village' => 'Kelurahan/Desa',
            'postal_code' => 'Kode Pos',
            'street_address' => 'Nama Jalan',
            'registered_at' => 'Tanggal Registrasi',
        ],
        'motor_interest' => [
            'report_date' => 'Date',
            'customer_name' => 'Customer',
            'phone' => 'Phone',
            'motor_name' => 'Motor',
            'context' => 'Context',
            'first_interest' => 'First Interest',
            'last_message_at' => 'Last Message',
            'conversation_status' => 'Conversation Status',
        ],
        'credit_applications' => [
            'application_number' => 'Application Number',
            'customer_name' => 'Customer',
            'phone' => 'Phone',
            'motor_name' => 'Motor',
            'cash_price' => 'Harga Cash',
            'down_payment' => 'DP',
            'tenor_months' => 'Tenor',
            'installment_source' => 'Cicilan Source',
            'status' => 'Status',
            'source' => 'Source',
            'created_at' => 'Created At',
            'submitted_at' => 'Submitted At',
        ],
        'dana_tunai' => [
            'report_date' => 'Date',
            'customer_name' => 'Customer',
            'phone' => 'Phone',
            'service' => 'Service',
            'status' => 'Status',
            'conversation' => 'Conversation',
            'last_activity' => 'Last Activity',
        ],
        'chat_activity' => [
            'activity_at' => 'Date',
            'conversation' => 'Conversation',
            'customer_name' => 'Customer',
            'context' => 'Context',
            'sender_type' => 'Sender Type',
            'message_count' => 'Message Count',
            'attachment_count' => 'Attachment Count',
            'last_activity' => 'Last Activity',
        ],
        'documents' => [
            'customer_name' => 'Customer',
            'application_number' => 'Application',
            'document_type' => 'Document Type',
            'filename' => 'Filename',
            'mime_type' => 'MIME',
            'size' => 'Size',
            'uploaded_at' => 'Uploaded At',
            'status' => 'Status',
        ],
        'customer_growth' => [
            'registration_date' => 'Date',
            'region' => 'Wilayah',
            'total_customers' => 'New Customers',
        ],
        'application_status' => [
            'application_type' => 'Application Type',
            'status' => 'Status',
            'total' => 'Total',
        ],
        'motor_interest_summary' => [
            'motor_name' => 'Motor',
            'conversation_count' => 'Conversations',
            'customer_count' => 'Customers',
            'first_interest' => 'First Interest',
            'last_message_at' => 'Last Message',
        ],
    ];

    public function reportTypes(): array
    {
        return self::REPORTS;
    }

    public function columns(string $type): array
    {
        return self::COLUMNS[$type] ?? throw new InvalidArgumentException('Unknown report type.');
    }

    public function query(string $type, array $filters): Builder
    {
        return match ($type) {
            'customers' => $this->customers($filters),
            'motor_interest' => $this->motorInterest($filters),
            'credit_applications' => $this->creditApplications($filters),
            'dana_tunai' => $this->danaTunai($filters),
            'chat_activity' => $this->chatActivity($filters),
            'documents' => $this->documents($filters),
            'customer_growth' => $this->customerGrowth($filters),
            'application_status' => $this->applicationStatus($filters),
            'motor_interest_summary' => $this->motorInterestSummary($filters),
            default => throw new InvalidArgumentException('Unknown report type.'),
        };
    }

    private function customers(array $filters): Builder
    {
        $query = DB::table('customers as c')
            ->join('regions as r', 'r.id', '=', 'c.region_id')
            ->join('districts as d', 'd.id', '=', 'c.district_id')
            ->join('villages as v', 'v.id', '=', 'c.village_id')
            ->select([
                'c.id as customer_id',
                'c.full_name as customer_name',
                'c.phone',
                'r.name as region',
                'd.name as district',
                'v.name as village',
                'c.postal_code',
                'c.street_address',
                'c.created_at as registered_at',
            ]);

        $this->applyDates($query, 'c.created_at', $filters);
        $this->applyRegion($query, 'c', $filters);

        return $query->orderByDesc('c.created_at')->orderByDesc('c.id');
    }

    private function motorInterest(array $filters): Builder
    {
        $query = DB::table('chat_conversations as cc')
            ->join('customers as c', 'c.id', '=', 'cc.customer_id')
            ->leftJoin('motor_products as mp', 'mp.id', '=', 'cc.motor_product_id')
            ->where('cc.context_type', 'motor')
            ->select([
                'cc.created_at as report_date',
                'c.full_name as customer_name',
                'c.phone',
                'mp.name as motor_name',
                'cc.context_type as context',
                'cc.created_at as first_interest',
                'cc.last_message_at',
                'cc.status as conversation_status',
            ]);

        $this->applyDates($query, 'cc.created_at', $filters);
        $this->applyRegion($query, 'c', $filters);
        $this->applyMotor($query, 'cc.motor_product_id', $filters);
        $this->applyStatus($query, 'cc.status', $filters);

        return $query->orderByDesc('cc.created_at')->orderByDesc('cc.id');
    }

    private function creditApplications(array $filters): Builder
    {
        $query = DB::table('credit_applications as ca')
            ->join('customers as c', 'c.id', '=', 'ca.customer_id')
            ->leftJoin('motor_products as mp', 'mp.id', '=', 'ca.motor_product_id')
            ->select([
                'ca.application_number',
                'c.full_name as customer_name',
                'c.phone',
                'mp.name as motor_name',
                'ca.cash_price',
                'ca.down_payment',
                'ca.tenor_months',
                'ca.installment_source',
                'ca.status',
                'ca.source',
                'ca.created_at',
                'ca.submitted_at',
            ]);

        $this->applyDates($query, 'ca.created_at', $filters);
        $this->applyRegion($query, 'c', $filters);
        $this->applyMotor($query, 'ca.motor_product_id', $filters);
        $this->applyStatus($query, 'ca.status', $filters);
        $this->applySource($query, 'ca.source', $filters);

        return $query->orderByDesc('ca.created_at')->orderByDesc('ca.id');
    }

    private function danaTunai(array $filters): Builder
    {
        $query = DB::table('dana_tunai_applications as da')
            ->join('customers as c', 'c.id', '=', 'da.customer_id')
            ->leftJoin('chat_conversations as cc', 'cc.id', '=', 'da.chat_conversation_id')
            ->select([
                'da.created_at as report_date',
                'c.full_name as customer_name',
                'c.phone',
                DB::raw("'Dana Tunai' as service"),
                'da.status',
                'da.chat_conversation_id as conversation',
                DB::raw('COALESCE(cc.last_message_at, da.updated_at, da.created_at) as last_activity'),
            ]);

        $this->applyDates($query, 'da.created_at', $filters);
        $this->applyRegion($query, 'c', $filters);
        $this->applyStatus($query, 'da.status', $filters);
        $this->applySource($query, 'da.source', $filters);

        return $query->orderByDesc('da.created_at')->orderByDesc('da.id');
    }

    private function chatActivity(array $filters): Builder
    {
        $messageTotals = DB::table('chat_messages')
            ->select('chat_conversation_id')
            ->selectRaw('COUNT(*) as message_count')
            ->groupBy('chat_conversation_id');
        $attachmentTotals = DB::table('chat_attachments as a')
            ->join('chat_messages as m', 'm.id', '=', 'a.message_id')
            ->select('m.chat_conversation_id')
            ->selectRaw('COUNT(a.id) as attachment_count')
            ->groupBy('m.chat_conversation_id');

        $query = DB::table('chat_conversations as cc')
            ->join('customers as c', 'c.id', '=', 'cc.customer_id')
            ->leftJoin('motor_products as mp', 'mp.id', '=', 'cc.motor_product_id')
            ->leftJoinSub($messageTotals, 'mt', 'mt.chat_conversation_id', '=', 'cc.id')
            ->leftJoinSub($attachmentTotals, 'at', 'at.chat_conversation_id', '=', 'cc.id')
            ->select([
                DB::raw('COALESCE(cc.last_message_at, cc.created_at) as activity_at'),
                'cc.id as conversation',
                'c.full_name as customer_name',
                'cc.context_type as context',
                DB::raw('(SELECT lm.sender_type FROM chat_messages lm WHERE lm.chat_conversation_id = cc.id ORDER BY lm.id DESC LIMIT 1) as sender_type'),
                DB::raw('COALESCE(mt.message_count, 0) as message_count'),
                DB::raw('COALESCE(at.attachment_count, 0) as attachment_count'),
                'cc.last_message_at as last_activity',
            ]);

        $this->applyDates($query, new Expression('COALESCE(cc.last_message_at, cc.created_at)'), $filters);
        $this->applyRegion($query, 'c', $filters);
        $this->applyMotor($query, 'cc.motor_product_id', $filters);
        $this->applyStatus($query, 'cc.status', $filters);

        return $query->orderByDesc('cc.last_message_at')->orderByDesc('cc.id');
    }

    private function documents(array $filters): Builder
    {
        $query = DB::table('customer_documents as cd')
            ->join('customers as c', 'c.id', '=', 'cd.customer_id')
            ->leftJoin('credit_applications as ca', 'ca.id', '=', 'cd.credit_application_id')
            ->leftJoin('dana_tunai_applications as da', 'da.id', '=', 'cd.dana_tunai_application_id')
            ->select([
                'c.full_name as customer_name',
                DB::raw('COALESCE(ca.application_number, da.application_number) as application_number'),
                'cd.document_type',
                'cd.original_name as filename',
                'cd.mime_type',
                'cd.size',
                DB::raw('COALESCE(cd.uploaded_at, cd.created_at) as uploaded_at'),
                'cd.status',
            ]);

        $this->applyDates($query, new Expression('COALESCE(cd.uploaded_at, cd.created_at)'), $filters);
        $this->applyRegion($query, 'c', $filters);
        $this->applyStatus($query, 'cd.status', $filters);

        return $query->orderByDesc('cd.uploaded_at')->orderByDesc('cd.id');
    }

    private function customerGrowth(array $filters): Builder
    {
        $query = DB::table('customers as c')
            ->join('regions as r', 'r.id', '=', 'c.region_id')
            ->selectRaw('DATE(c.created_at) as registration_date, r.name as region, COUNT(c.id) as total_customers')
            ->groupByRaw('DATE(c.created_at), r.name');

        $this->applyDates($query, 'c.created_at', $filters);
        $this->applyRegion($query, 'c', $filters);

        return $query->orderByDesc('registration_date')->orderBy('region');
    }

    private function applicationStatus(array $filters): Builder
    {
        $credit = DB::table('credit_applications as ca')
            ->join('customers as c', 'c.id', '=', 'ca.customer_id')
            ->selectRaw("'credit_application' as application_type, ca.status, COUNT(ca.id) as total")
            ->groupBy('ca.status');
        $this->applyDates($credit, 'ca.created_at', $filters);
        $this->applyRegion($credit, 'c', $filters);
        $this->applyMotor($credit, 'ca.motor_product_id', $filters);
        $this->applyStatus($credit, 'ca.status', $filters);
        $this->applySource($credit, 'ca.source', $filters);

        $dana = DB::table('dana_tunai_applications as da')
            ->join('customers as c', 'c.id', '=', 'da.customer_id')
            ->selectRaw("'dana_tunai' as application_type, da.status, COUNT(da.id) as total")
            ->groupBy('da.status');
        $this->applyDates($dana, 'da.created_at', $filters);
        $this->applyRegion($dana, 'c', $filters);
        $this->applyStatus($dana, 'da.status', $filters);
        $this->applySource($dana, 'da.source', $filters);

        return $credit->unionAll($dana)
            ->orderBy('application_type')
            ->orderBy('status');
    }

    private function motorInterestSummary(array $filters): Builder
    {
        $query = DB::table('chat_conversations as cc')
            ->join('customers as c', 'c.id', '=', 'cc.customer_id')
            ->leftJoin('motor_products as mp', 'mp.id', '=', 'cc.motor_product_id')
            ->where('cc.context_type', 'motor')
            ->selectRaw(
                "COALESCE(mp.name, 'Motor tidak diketahui') as motor_name, COUNT(cc.id) as conversation_count, COUNT(DISTINCT cc.customer_id) as customer_count, MIN(cc.created_at) as first_interest, MAX(cc.last_message_at) as last_message_at"
            )
            ->groupBy('mp.name');

        $this->applyDates($query, 'cc.created_at', $filters);
        $this->applyRegion($query, 'c', $filters);
        $this->applyMotor($query, 'cc.motor_product_id', $filters);
        $this->applyStatus($query, 'cc.status', $filters);

        return $query->orderByDesc('conversation_count')->orderBy('motor_name');
    }

    private function applyDates(Builder $query, string|Expression $column, array $filters): void
    {
        if (!empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }

    private function applyRegion(Builder $query, string $alias, array $filters): void
    {
        if (!empty($filters['region_id'])) {
            $query->where($alias . '.region_id', $filters['region_id']);
        }
    }

    private function applyMotor(Builder $query, string $column, array $filters): void
    {
        if (!empty($filters['motor_id'])) {
            $query->where($column, $filters['motor_id']);
        }
    }

    private function applyStatus(Builder $query, string $column, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where($column, $filters['status']);
        }
    }

    private function applySource(Builder $query, string $column, array $filters): void
    {
        if (!empty($filters['source'])) {
            $query->where($column, $filters['source']);
        }
    }
}
