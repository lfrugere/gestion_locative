<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    public function test_was_edited_is_false_when_the_note_was_never_modified(): void
    {
        $note = new Note(['updated_by' => null]);

        $this->assertFalse($note->wasEdited());
    }

    public function test_was_edited_is_true_once_updated_by_is_set(): void
    {
        $note = new Note(['updated_by' => 1]);

        $this->assertTrue($note->wasEdited());
    }
}
