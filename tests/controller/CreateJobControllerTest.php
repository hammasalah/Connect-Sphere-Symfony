<?php
// // tests/Controller/CreateJobControllerTest.php
// namespace App\Tests\Controller;

// use App\Entity\Jobs;
// use Doctrine\ORM\Tools\SchemaTool;
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// class CreateJobControllerTest extends WebTestCase
// {
//     public function testCreateJobFormSubmission(): void
//     {
//         // 1) Create client (boots kernel once)
//         $client = static::createClient();

//         // 2) Recreate schema on the in-memory DB
//         $em = $client->getContainer()->get('doctrine')->getManager();
//         $metadata = $em->getMetadataFactory()->getAllMetadata();
//         if (!empty($metadata)) {
//             $schemaTool = new SchemaTool($em);
//             // drop & recreate (optional drop for truly clean slate)
//             $schemaTool->dropDatabase(); 
//             $schemaTool->createSchema($metadata);
//         }

//         // 3) Request the form page
//         $crawler = $client->request('GET', '/create/job');
//         $this->assertResponseIsSuccessful();

//         // 4) Fill and submit
//         $form = $crawler->selectButton('Submit')->form([
//             'create_job_form[jobTitle]'            => 'Backend Developer',
//             'create_job_form[eventTitle]'          => 'Tech Conference 2025',
//             'create_job_form[jobLocation]'         => 'Tunis, TN',
//             'create_job_form[employmentType]'      => 'Full-time',
//             'create_job_form[applicationDeadLine]' => '2025-06-30',
//             'create_job_form[minSalary]'           => '1000',
//             'create_job_form[maxSalary]'           => '2000',
//             'create_job_form[currency]'            => 'TND',
//             'create_job_form[jobDescreption]'      => 'Develop and maintain APIs.',
//             'create_job_form[recruiterName]'       => 'Acme HR',
//             'create_job_form[recruiterEmail]'      => 'hr@acme.com',
//         ]);
//         $client->submit($form);

//         // 5) Assert redirect
//         $this->assertResponseRedirects('/create/job');

//         // 6) Follow and check form exists again
//         $client->followRedirect();
//         $this->assertSelectorExists('form');

//         // 7) Verify persistence
//         $jobs = $em->getRepository(Jobs::class)
//                    ->findBy(['jobTitle' => 'Backend Developer']);
//         $this->assertCount(1, $jobs);
//     }
// }

// tests/Controller/CreateJobControllerTest.php
namespace App\Tests\Controller;

use App\Entity\Jobs;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateJobControllerTest extends WebTestCase
{
    /**
     * This test should succeed: it creates a job and asserts it was persisted.
     */
    public function testCreateJobFormSubmissionSuccess(): void
    {
        $client = static::createClient();

        // Recreate schema on the in-memory DB
        $em = $client->getContainer()->get('doctrine')->getManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->dropDatabase();
            $schemaTool->createSchema($metadata);
        }

        // Request the form page
        $crawler = $client->request('GET', '/create/job');
        $this->assertResponseIsSuccessful();

        // Fill and submit the form
        $form = $crawler->selectButton('Submit')->form([
            'create_job_form[jobTitle]'            => 'Backend Developer',
            'create_job_form[eventTitle]'          => 'Tech Conference 2025',
            'create_job_form[jobLocation]'         => 'Tunis, TN',
            'create_job_form[employmentType]'      => 'Full-time',
            'create_job_form[applicationDeadLine]' => '2025-06-30',
            'create_job_form[minSalary]'           => '1000',
            'create_job_form[maxSalary]'           => '2000',
            'create_job_form[currency]'            => 'TND',
            'create_job_form[jobDescreption]'      => 'Develop and maintain APIs.',
            'create_job_form[recruiterName]'       => 'Acme HR',
            'create_job_form[recruiterEmail]'      => 'hr@acme.com',
        ]);
        $client->submit($form);

        // Assert redirect
        $this->assertResponseRedirects('/create/job');
        $client->followRedirect();
        $this->assertSelectorExists('form');

        // Verify persistence
        $jobs = $em->getRepository(Jobs::class)
                   ->findBy(['jobTitle' => 'Backend Developer']);
        $this->assertCount(1, $jobs, 'Expected exactly 1 job with title "Backend Developer"');
    }

    /**
     * This test is intentionally set to fail: it asserts zero jobs persisted.
     */
    public function testCreateJobFormSubmissionFailure(): void
    {
        $client = static::createClient();

        // Recreate schema
        $em = $client->getContainer()->get('doctrine')->getManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->dropDatabase();
            $schemaTool->createSchema($metadata);
        }

        // Request and submit the same form
        $crawler = $client->request('GET', '/create/job');
        $form = $crawler->selectButton('Submit')->form([
            'create_job_form[jobTitle]'            => 'QA Engineer',
            'create_job_form[eventTitle]'          => 'Testing Summit',
            'create_job_form[jobLocation]'         => 'Remote',
            'create_job_form[employmentType]'      => 'Contract',
            'create_job_form[applicationDeadLine]' => '2025-07-15',
            'create_job_form[minSalary]'           => '1500',
            'create_job_form[maxSalary]'           => '2500',
            'create_job_form[currency]'            => 'USD',
            'create_job_form[jobDescreption]'      => 'Write test plans.',
            'create_job_form[recruiterName]'       => 'QA Corp',
            'create_job_form[recruiterEmail]'      => 'qa@corp.com',
        ]);
        $client->submit($form);

        // Follow redirect
        $client->followRedirect();

        // Intentionally assert zero jobs to cause a failure
        $jobs = $em->getRepository(Jobs::class)
                   ->findBy(['jobTitle' => 'QA Engineer']);
        $this->assertCount(0, $jobs, 'This assertion is expected to fail because one job should exist.');
    }
}
