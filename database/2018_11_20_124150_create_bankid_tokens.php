<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankidTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bankid_tokens', function (Blueprint $table) {
            $table->string('token', 150)->primary();
            $table->string('order_ref', 100);
            $table->uuid('user_uuid');
            $table->string('signed_by_name', 100);
            $table->string('signed_by_pnr', 20);

            $table->string('signable_type')->nullable();
            $table->string('signable_id')->nullable();
            //$table->nullableMorphs('signable');
            $table->string('action');
            $table->boolean('used')->default(0);
            $table->boolean('revoked')->default(0);
            $table->timestamps();
            $table->dateTime('expires_at');

            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bankid_tokens');
    }
}
