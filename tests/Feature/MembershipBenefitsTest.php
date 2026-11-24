<?php

use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('level cards list each benefit while respecting explicit lines', function (?string $benefits, array $expectedItems): void {
    $user = User::factory()->create();
    MembershipLevel::factory()->create(['benefits' => $benefits]);

    $response = $this->actingAs($user)->get(route('levels.index'))->assertOk();

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $nodes = (new DOMXPath($document))->query('//article//ul/li');
    $items = array_map(fn (DOMNode $node): string => trim($node->textContent), iterator_to_array($nodes));
    expect($items)->toBe($expectedItems);
})->with([
    'legacy comma separated paragraph' => [
        'All-inclusive access to courses, certificates, 1-on-1 mentoring sessions, and exclusive workshops.',
        ['All-inclusive access to courses', 'Certificates', '1-on-1 mentoring sessions', 'Exclusive workshops.'],
    ],
    'separate lines preserve commas within a benefit' => [
        "Courses, labs, and workshops\nCertificate of completion\nMentoring and career guidance",
        ['Courses, labs, and workshops', 'Certificate of completion', 'Mentoring and career guidance'],
    ],
    'typed bullets and numbering' => [
        "- Course access\n* Completion certificate\n• Community support\n1. Downloadable guides",
        ['Course access', 'Completion certificate', 'Community support', 'Downloadable guides'],
    ],
    'missing benefits' => [null, ['Standard membership access']],
    'whitespace only' => [" \r\n  ", ['Standard membership access']],
    'commas in numbers stay intact' => [
        'Courses for 1,000 learners, certificates, and mentoring.',
        ['Courses for 1,000 learners', 'Certificates', 'Mentoring.'],
    ],
    'and within a benefit stays intact' => [
        'Research and development workshops', ['Research and development workshops'],
    ],
    'blank lines and windows line endings' => [
        "  Course access  \r\n\r\n  Community support  ", ['Course access', 'Community support'],
    ],
    'one explicit bullet preserves its commas' => [
        '- Courses, labs, and workshops', ['Courses, labs, and workshops'],
    ],
    'empty comma list' => [', , and ', ['Standard membership access']],
]);

test('registration receives separate benefit items with quotes and markup preserved as text', function (): void {
    $user = User::factory()->create();
    $expectedItems = ['Course access', 'Certificate "Plus"', "O'Reilly's <img src=x onerror=alert(1)>"];
    $level = MembershipLevel::factory()->create(['benefits' => implode("\n", $expectedItems)]);

    $response = $this->actingAs($user)->get(route('members.create'))->assertOk();

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $encodedItems = (new DOMXPath($document))->evaluate('string(//select[@id="levelSelect"]/option[@value="'.$level->id.'"]/@data-benefits)');
    expect(json_decode($encodedItems, true, 512, JSON_THROW_ON_ERROR))->toBe($expectedItems);
    $response->assertDontSee('<img src=x onerror=alert(1)>', false);
});

test('level benefit cards escape HTML instead of creating elements', function (): void {
    $user = User::factory()->create();
    $markup = '<img src=x onerror=alert(1)>';
    MembershipLevel::factory()->create(['benefits' => "Course access\n".$markup]);

    $this->actingAs($user)->get(route('levels.index'))->assertOk()
        ->assertSee($markup)->assertDontSee($markup, false);
});
