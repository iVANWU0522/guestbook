<?php

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\ResponseInterface;

class SpamCheckerTest extends TestCase
{
    public function testSpamScoreWithInvalidRequest(): void
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $httpClient = new MockHttpClient([
            new MockResponse('invalid', [
                'response_headers' => [
                    'x-akismet-debug-help: Invalid key',
                ],
            ]),
        ]);
        $checker = new SpamChecker($httpClient, 'api-key');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid (Invalid key)');
        $checker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider getComments
     */
    public function testSpamScore(int $expectedScore, MockResponse $response, Comment $comment, array $context): void
    {
        $client = new MockHttpClient([$response]);
        $checker = new SpamChecker($client, 'api-key');

        $score = $checker->getSpamScore($comment, $context);
        $this->assertSame($expectedScore, $score);
    }


    public function getComments(): iterable
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatant_spam' => [2, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];
    }
}
