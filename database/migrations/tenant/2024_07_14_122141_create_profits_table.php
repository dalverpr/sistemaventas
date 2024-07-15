<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProfitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profits', function (Blueprint $table) {
            $table->increments('id');
            $table->float('fbpm',10,2)->nullable()->default(0);
            $table->float('purchases',10,2)->nullable()->default(0);
            $table->float('expenses',10,2)->nullable()->default(0);
            $table->float('income',10,2)->nullable()->default(0);
            $table->float('sales',10,2)->nullable()->default(0);
            $table->float('fbam',10,2)->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profits');
    }
}