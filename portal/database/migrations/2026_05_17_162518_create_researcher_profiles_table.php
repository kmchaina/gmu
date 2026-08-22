<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('researcher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Professional details
            $table->string('title')->nullable();                  // Dr., Prof., Mr., Ms.
            $table->string('position')->nullable();               // Principal Research Scientist
            $table->string('department')->nullable();
            $table->string('research_center')->nullable();
            $table->text('bio')->nullable();
            $table->json('expertise_areas')->nullable();          // ['Epidemiology','Malaria','One Health']
            $table->json('qualifications')->nullable();           // [{'degree':'PhD','field':'Epidemiology','institution':'MUHAS','year':2010}]

            // Publications (manual entry until ORCID linked)
            $table->json('publications')->nullable();             // [{'title':'...','journal':'...','year':2022,'doi':'...'}]

            // ORCID integration placeholder
            $table->string('orcid_id', 20)->nullable()->unique(); // e.g. 0000-0002-1825-0097
            $table->string('orcid_access_token')->nullable();
            $table->timestamp('orcid_synced_at')->nullable();

            // Contact & links
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('researchgate_url')->nullable();

            // Stats (denormalised for performance)
            $table->unsignedSmallInteger('total_grants_led')->default(0);
            $table->unsignedSmallInteger('total_grants_contributed')->default(0);
            $table->decimal('total_funding_secured', 18, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('researcher_profiles');
    }
};
