<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @Todo: Fix this test
 * Caused by
 * ErrorException: An exception occurred in the driver: SQLSTATE[08006] [7] connection to server at "127.0.0.1", port 5432 failed: FATAL:  password authentication failed for user "app"
 */
class ConferenceControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }


    public function testCommentSubmission(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/conference/amsterdam-2019');

        $client->submitForm('Submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]' => 'Some feedback from an automated functional test',
            'comment_form[email]' => $email = 'me@automat.ed',
            'comment_form[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);

        $this->assertResponseRedirects();

        // simulate comment validation
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }


    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There is one comments")');
    }
}