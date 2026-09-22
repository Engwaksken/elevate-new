<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tin')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('bank_account_number_encrypted')->nullable();
            $table->enum('status', ['pending','approved','suspended','inactive'])->default('pending');
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->text('performance_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workplan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('financial_year')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('estimated_budget', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->enum('status', ['draft','submitted','approved','closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('procurement_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workplan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->date('required_date')->nullable();
            $table->string('funding_source')->nullable();
            $table->text('justification')->nullable();
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->string('currency', 3)->default('UGX');
            $table->enum('status', [
                'draft','submitted','manager_approved','finance_approved',
                'procurement_review','approved','rejected','sourcing',
                'ordered','received','closed','cancelled'
            ])->default('draft');
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit')->nullable();
            $table->decimal('estimated_unit_cost', 15, 2)->nullable();
            $table->decimal('estimated_total', 15, 2)->nullable();
            $table->boolean('is_asset')->default(false);
            $table->timestamps();
        });

        Schema::create('purchase_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('approval_stage');
            $table->enum('decision', ['approved','rejected','returned']);
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->useCurrent();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('quotation_number')->nullable();
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('UGX');
            $table->string('document_path')->nullable();
            $table->enum('status', ['received','evaluated','selected','not_selected'])->default('received');
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_request_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('quotation_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('technical_score', 5, 2)->nullable();
            $table->decimal('financial_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->boolean('recommended')->default(false);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('UGX');
            $table->enum('status', ['draft','issued','partially_received','received','cancelled','closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_request_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->boolean('is_asset')->default(false);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->date('received_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('delivery_note_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft','confirmed','rejected'])->default('draft');
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('quantity_accepted', 12, 2);
            $table->decimal('quantity_rejected', 12, 2)->default(0);
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('asset_tag')->nullable()->unique();
            $table->foreignId('asset_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->index();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goods_receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('funding_source')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('custodian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('condition')->default('good');
            $table->date('warranty_end_date')->nullable();
            $table->enum('status', ['available','assigned','in_use','under_maintenance','damaged','lost','retired','disposed'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->date('expected_return_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->text('return_condition')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active','returned','lost','damaged'])->default('active');
            $table->timestamps();
        });

        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transfer_date');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('reported_date');
            $table->string('maintenance_type')->nullable();
            $table->text('issue_description')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->date('completed_date')->nullable();
            $table->text('resolution')->nullable();
            $table->enum('status', ['reported','in_progress','completed','cancelled'])->default('reported');
            $table->timestamps();
        });

        Schema::create('asset_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('stocktake_date');
            $table->string('location')->nullable();
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft','in_progress','completed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('asset_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->boolean('found')->default(false);
            $table->string('observed_condition')->nullable();
            $table->string('observed_location')->nullable();
            $table->text('variance_notes')->nullable();
            $table->timestamps();
            $table->unique(['asset_stocktake_id','asset_id']);
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('requested_date');
            $table->text('reason');
            $table->string('disposal_method')->nullable();
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->enum('status', ['requested','approved','completed','rejected'])->default('requested');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_stocktake_items');
        Schema::dropIfExists('asset_stocktakes');
        Schema::dropIfExists('asset_maintenance');
        Schema::dropIfExists('asset_transfers');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('quotation_evaluations');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('purchase_request_approvals');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('procurement_plans');
        Schema::dropIfExists('suppliers');
    }
};
