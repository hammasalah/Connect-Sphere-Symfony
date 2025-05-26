<?php
// tests/Functional/RegistrationControllerTest.php
namespace App\Tests\Functional;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PhpCsFixer\Fixer\NamespaceNotation\CleanNamespaceFixer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        // Récupère l'EntityManager en mode test
        $this->em = $this->client->getContainer()
                                 ->get(EntityManagerInterface::class);

        // Reconstruit le schéma à blanc dans la DB en mémoire
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $tool = new SchemaTool($this->em);
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }
    }

    public function testDisplayRegistrationForm(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertCount(1, $crawler->filter('input[name="registration_form[username]"]'));
        $this->assertCount(1, $crawler->filter('input[name="registration_form[email]"]'));
        $this->assertCount(1, $crawler->filter('input[name="registration_form[age]"]'));
        $this->assertCount(1, $crawler->filter('select[name="registration_form[gender]"]'));
        $this->assertCount(1, $crawler->filter('input[name="registration_form[plainPassword]"]'));
        $this->assertCount(1, $crawler->filter('input[name="registration_form[agreeTerms]"]'));
    }

    public function testSubmitRegistrationSuccess(): void
    {
        $crawler = $this->client->request('GET', '/register');

        // Remplit et soumet le formulaire
        $form = $crawler->filter('form')->form([
            'registration_form[username]'      => 'meyssem',
            'registration_form[email]'         => 'meyssem@example.com',
            'registration_form[age]'           => 25,
            'registration_form[gender]'        => 'male',
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]'    => 1,
        ]);
        $this->client->submit($form);

        // Doit rediriger vers /login
        $this->assertResponseRedirects('/login');

        // Suit la redirection et vérifie le message de succès
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Registration successful', $this->client->getResponse()->getContent());

        // Vérifie la création en base
        $user = $this->em->getRepository(Users::class)
                        ->findOneBy(['email' => 'meyssem@example.com']);
        $this->assertNotNull($user, 'L’utilisateur doit exister en base');
        $this->assertSame('meyssem', $user->getUsername());
    }

    public function testSubmitRegistrationFailure(): void
    {
        $crawler = $this->client->request('GET', '/register');

        // Remplit le formulaire avec des données invalides
        $form = $crawler->filter('form')->form([
            'registration_form[username]'      => '',
            'registration_form[email]'         => 'not-an-email',
            'registration_form[age]'           => 16,
            'registration_form[plainPassword]' => '123',
            // omit gender and agreeTerms to simulate missing choice
        ]);
        $this->client->submit($form);

        // On reste sur /register
        $this->assertResponseStatusCodeSame(200);

        // Vérifie la présence des messages d'erreur dans le contenu
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Please enter a username', $content);
        $this->assertStringContainsString('You must be at least 18 years old', $content);
    }
}
