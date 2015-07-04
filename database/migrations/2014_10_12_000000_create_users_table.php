<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('badges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('login');
            $table->string('type'); // type of login, i.e. github, google, etc.
            $table->string('avatar_url');
            $table->unique(array('login', 'type'));
            $table->timestamps();
        });

        Schema::create('issuers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unique('name');
            $table->string('api_key');
            $table->integer('owner_user_id')->unsigned();
            $table->foreign('owner_user_id')->references('id')->on('users');

            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('issuer_id')->unsigned()->nullable();
            $table->foreign('issuer_id')->references('id')->on('issuers');

            $table->string('name');
            $table->timestamps();
        });




        Schema::create('users_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('tag_id')->unsigned();
            $table->foreign('tag_id')->references('id')->on('tags');
            $table->unique(array('user_id', 'tag_id'));
            $table->integer('points');
            $table->timestamps();
        });


        Schema::create('users_tags_history', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('origin_user_id')->nullable()->unsigned();
            $table->foreign('origin_user_id')->references('id')->on('users');

            $table->integer('tag_id')->unsigned();
            $table->foreign('tag_id')->references('id')->on('tags');

            $table->integer('points');
            $table->timestamps();
        });



        Schema::create('users_badges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tag_id')->unsigned();
            $table->foreign('tag_id')->references('id')->on('tags');

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('badge');

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
        Schema::drop('users');
        Schema::drop('badges');
        Schema::drop('tags');
        Schema::drop('users_tags');
        Schema::drop('users_tags_history');
        Schema::drop('issuers');
        Schema::drop('users_badges');
    }
}
