<?php declare(strict_types=1);

namespace App\Tests\Controller\Entry\Comment;

use App\Tests\WebTestCase;

class EntryCommentVotersControllerTest extends WebTestCase
{
    public function testCanSeeEntryCommentVoters()
    {
        $client = $this->createClient();
        $client->loginUser($this->getUserByUsername('regularUser'));

        $entry = $this->getEntryByTitle('example');

        $comment = $this->createEntryComment('comment1', $entry);
        $child = $this->createEntryComment('child', null, $this->getUserByUsername('user1'), $comment);

        $this->createVote(0, $child, $this->getUserByUsername('regularUser'));
        $this->createVote(1, $child, $this->getUserByUsername('user2'));

        $crawler = $client->request('GET', sprintf('/m/polityka/t/%d/-/komentarz/%d/głosy', $entry->getId(), $child->getId()));

        $this->assertSelectorTextContains('blockquote', 'child');
        $this->assertCount(2, $crawler->filter('.kbin-voters .card'));
    }

    public function testXmlCanSeeEntryCommentVoters()
    {
        $client = $this->createClient();
        $client->loginUser($this->getUserByUsername('regularUser'));

        $entry = $this->getEntryByTitle('example');

        $comment = $this->createEntryComment('comment1', $entry);
        $child = $this->createEntryComment('child', null, $this->getUserByUsername('user1'), $comment);

        $this->createVote(0, $child, $this->getUserByUsername('regularUser'));
        $this->createVote(1, $child, $this->getUserByUsername('user2'));

        $client->setServerParameter('HTTP_X-Requested-With', 'XMLHttpRequest');

        $client->request('GET', sprintf('/m/polityka/t/%d/-/komentarz/%d/głosy', $entry->getId(), $child->getId()));

        $this->assertStringContainsString('kbin-inline-voters', $client->getResponse()->getContent());
    }
}
