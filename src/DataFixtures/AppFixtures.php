<?php

namespace App\DataFixtures;

use App\Entity\Assignment;
use App\Entity\Classroom;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Check if DB is already seeded to avoid duplication
        $existingAdmin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@demo.fr']);
        if ($existingAdmin !== null) {
            echo "Database already seeded. Skipping...\n";
            return;
        }

        // 1. Create Admin
        $admin = new User();
        $admin->setEmail('admin@demo.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setFirstName('Georges');
        $admin->setLastName('Dubois');
        $admin->setAddress('1 rue de la Paix');
        $admin->setCity('Paris');
        $admin->setPostalCode('75000');
        $admin->setIsVerified(true);
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // 2. Create Prof
        $prof = new User();
        $prof->setEmail('prof@demo.fr');
        $prof->setRoles(['ROLE_TEACHER']);
        $prof->setFirstName('Marie');
        $prof->setLastName('Curie');
        $prof->setAddress('2 rue de la Paix');
        $prof->setCity('Paris');
        $prof->setPostalCode('75000');
        $prof->setIsVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword($prof, 'prof');
        $prof->setPassword($hashedPassword);
        $manager->persist($prof);

        // 3. Create Eleve
        $eleve = new User();
        $eleve->setEmail('eleve@demo.fr');
        $eleve->setRoles(['ROLE_USER']);
        $eleve->setFirstName('Lucas');
        $eleve->setLastName('Martin');
        $eleve->setAddress('3 rue de la Paix');
        $eleve->setCity('Paris');
        $eleve->setPostalCode('75000');
        $eleve->setIsVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword($eleve, 'eleve');
        $eleve->setPassword($hashedPassword);
        $manager->persist($eleve);

        // 4. Create Fake Classrooms taught by Prof
        $classrooms = [];
        for ($i = 1; $i <= 3; $i++) {
            $classroom = new Classroom();
            $classroom->setName('Classe ' . $faker->word . ' ' . $i);
            $classroom->setTeacher($prof);
            $manager->persist($classroom);
            $classrooms[] = $classroom;
            
            // Add our known eleve to each classroom
            $classroom->addStudent($eleve);
        }

        // 5. Create Many Fake Users (Students)
        $fakeStudents = [];
        for ($i = 0; $i < 30; $i++) {
            $fakeEleve = new User();
            $fakeEleve->setEmail($faker->unique()->safeEmail);
            $fakeEleve->setRoles(['ROLE_USER']);
            $fakeEleve->setFirstName($faker->firstName);
            $fakeEleve->setLastName($faker->lastName);
            $fakeEleve->setAddress($faker->streetAddress);
            $fakeEleve->setCity($faker->city);
            // Ensure exactly 5 digits for postal code as required by Entity constraint
            $fakeEleve->setPostalCode($faker->numerify('#####')); 
            $fakeEleve->setIsVerified(true);

            $hashedPassword = $this->passwordHasher->hashPassword($fakeEleve, 'password');
            $fakeEleve->setPassword($hashedPassword);
            $manager->persist($fakeEleve);
            
            $fakeStudents[] = $fakeEleve;
        }

        // 6. Add fake students to classrooms and create assignments
        foreach ($classrooms as $classroom) {
            // Assign some random fake students to this classroom
            shuffle($fakeStudents);
            for ($j = 0; $j < 10; $j++) {
                $classroom->addStudent($fakeStudents[$j]);
            }

            // Create assignments for this classroom
            for ($k = 1; $k <= 4; $k++) {
                $assignment = new Assignment();
                $assignment->setTitle($faker->sentence(4));
                $assignment->setDescription($faker->paragraph(3));
                $assignment->setDueDate($faker->dateTimeBetween('now', '+2 weeks'));
                $assignment->setAllowLateSubmissions($faker->boolean(30));
                $assignment->setClassroom($classroom);
                $manager->persist($assignment);
            }
        }

        $manager->flush();
    }
}
