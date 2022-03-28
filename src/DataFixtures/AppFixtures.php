<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Post;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('de_DE');

        for ($i=1; $i <= 5; $i++) { 
            
            $category = new Category();
            $category->setTitle($faker->word);    
            $manager->persist($category);

            // Add Eintrag
            for ($j=1; $j <= 6; $j++) { 
                
                $post = new Post();
                $post->setTitle($faker->sentence(3));
                $post->setContent($faker->text(500));
                $post->setCreatedAt($faker->dateTimeBetween('- 3 months'));
                $post->setCategory($category);
        
                $manager->persist($post);
            }
        }

        $manager->flush();
    }
}
