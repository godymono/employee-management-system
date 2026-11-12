<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pms_roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('pms_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_role_id')->constrained('pms_roles')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('default_weight', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pms_kpi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_category_id')->constrained('pms_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('default_weight', 5, 2)->default(0);
            $table->string('default_target')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pms_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('title');
            $table->string('status')->default('draft'); // draft, active, closed
            $table->date('h1_opens_at')->nullable();
            $table->date('h1_closes_at')->nullable();
            $table->date('h2_opens_at')->nullable();
            $table->date('h2_closes_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pms_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_cycle_id')->constrained('pms_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('pms_role_id')->constrained('pms_roles')->restrictOnDelete();
            $table->string('status')->default('draft');
            // draft -> targets_set -> h1_self_draft -> h1_submitted -> h1_reviewed
            // -> h2_self_draft -> h2_submitted -> h2_reviewed -> finalized
            $table->timestamp('targets_locked_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->decimal('h1_score', 6, 2)->nullable();
            $table->decimal('h2_score', 6, 2)->nullable();
            $table->decimal('annual_score', 6, 2)->nullable();
            $table->string('rating_band')->nullable();
            $table->text('final_comment')->nullable();
            $table->timestamps();

            $table->unique(['pms_cycle_id', 'employee_id']);
        });

        Schema::create('pms_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_plan_id')->constrained('pms_plans')->cascadeOnDelete();
            $table->foreignId('pms_kpi_item_id')->nullable()->constrained('pms_kpi_items')->nullOnDelete();
            $table->string('category_name');
            $table->decimal('category_weight', 5, 2)->default(0);
            $table->string('kpi_name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0);
            $table->string('target')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pms_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_plan_id')->constrained('pms_plans')->cascadeOnDelete();
            $table->string('period'); // H1, H2
            $table->string('stage'); // self, manager
            $table->string('status')->default('draft'); // draft, submitted
            $table->decimal('total_score', 6, 2)->nullable();
            $table->text('overall_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['pms_plan_id', 'period', 'stage']);
        });

        Schema::create('pms_review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_review_id')->constrained('pms_reviews')->cascadeOnDelete();
            $table->foreignId('pms_plan_item_id')->constrained('pms_plan_items')->cascadeOnDelete();
            $table->decimal('achievement', 6, 2)->nullable(); // 0-100 %
            $table->string('actual_result')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('weighted_score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['pms_review_id', 'pms_plan_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pms_review_scores');
        Schema::dropIfExists('pms_reviews');
        Schema::dropIfExists('pms_plan_items');
        Schema::dropIfExists('pms_plans');
        Schema::dropIfExists('pms_cycles');
        Schema::dropIfExists('pms_kpi_items');
        Schema::dropIfExists('pms_categories');
        Schema::dropIfExists('pms_roles');
    }
};
