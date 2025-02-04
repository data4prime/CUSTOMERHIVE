<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Model>
 */
class QlikConfFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'confname' => $this->faker->word,
            'type' => $this->faker->word,
            'qrsurl' => $this->faker->url,
            'endpoint' => $this->faker->url,
            'QRSCertfile' => $this->faker->word,
            'QRSCertkeyfile' => $this->faker->word,
            'QRSCertkeyfilePassword' => $this->faker->word,
            'url' => $this->faker->url,
            'keyid' => $this->faker->word,
            'issuer' => $this->faker->word,
            'web_int_id' => $this->faker->numberBetween(1, 100),
            'private_key' => $this->faker->text,
            'debug' => $this->faker->boolean,
        ];
    }
}
