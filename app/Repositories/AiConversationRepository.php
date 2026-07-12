<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * SyncAI's conversation threads (sidebar list) and their message log.
 * Messages are kept close to the OpenAI/Groq wire format (role, content,
 * tool_calls, tool_call_id, name) since the whole point of persisting them
 * is to replay history straight back into the next API call — a bespoke
 * Model class would just be a lossy round-trip through the same shape.
 */
final class AiConversationRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly int  $adminId,
        private readonly ?int $companyId,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), (int) Session::userId(), Session::companyId());
    }

    /** True if this conversation belongs to the current admin (never shared across admins). */
    public function ownedByCurrentAdmin(int $conversationId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM AiConversations WHERE id = :id AND admin_id = :aid LIMIT 1');
        $stmt->execute(['id' => $conversationId, 'aid' => $this->adminId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array<array{id:int, title:?string, updated_at:string}> most recent first, for the sidebar. */
    public function listForAdmin(): array
    {
        $stmt = $this->db->prepare('
            SELECT id, title, updated_at
            FROM AiConversations
            WHERE admin_id = :aid
            ORDER BY updated_at DESC
            LIMIT 100
        ');
        $stmt->execute(['aid' => $this->adminId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(): int
    {
        $stmt = $this->db->prepare('INSERT INTO AiConversations (admin_id, company_id) VALUES (:aid, :cid)');
        $stmt->execute(['aid' => $this->adminId, 'cid' => $this->companyId]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $conversationId): void
    {
        $this->db->prepare('DELETE FROM AiMessages WHERE conversation_id = :id')->execute(['id' => $conversationId]);
        $this->db->prepare('DELETE FROM AiConversations WHERE id = :id')->execute(['id' => $conversationId]);
    }

    /** Sets the title once, from the first exchange — never overwritten after that. */
    public function setTitleIfMissing(int $conversationId, string $title): void
    {
        $this->db->prepare("UPDATE AiConversations SET title = :title WHERE id = :id AND title IS NULL")
            ->execute(['title' => $title, 'id' => $conversationId]);
    }

    public function touch(int $conversationId): void
    {
        $this->db->prepare('UPDATE AiConversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => $conversationId]);
    }

    /**
     * Full message log for one conversation, in Groq wire format plus the
     * extras (id, timestamp, attachments) the UI needs to render bubbles.
     * @return array<array<string,mixed>>
     */
    public function messagesFor(int $conversationId): array
    {
        $stmt = $this->db->prepare('
            SELECT id, role, content, tool_calls, tool_call_id, tool_name, attachments, created_at
            FROM AiMessages
            WHERE conversation_id = :id
            ORDER BY id
        ');
        $stmt->execute(['id' => $conversationId]);
        return array_map(static function (array $row): array {
            $row['tool_calls']  = $row['tool_calls']  !== null ? json_decode((string) $row['tool_calls'], true) : null;
            $row['attachments'] = $row['attachments'] !== null ? json_decode((string) $row['attachments'], true) : [];
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @param array<int,array<string,mixed>>|null $toolCalls
     * @param array<int,array<string,mixed>>|null $attachments
     */
    public function appendMessage(
        int $conversationId,
        string $role,
        ?string $content,
        ?array $toolCalls = null,
        ?string $toolCallId = null,
        ?string $toolName = null,
        ?array $attachments = null,
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO AiMessages (conversation_id, role, content, tool_calls, tool_call_id, tool_name, attachments)
            VALUES (:cid, :role, :content, :tool_calls, :tool_call_id, :tool_name, :attachments)
        ');
        $stmt->execute([
            'cid'          => $conversationId,
            'role'         => $role,
            'content'      => $content,
            'tool_calls'   => $toolCalls !== null ? json_encode($toolCalls, JSON_UNESCAPED_UNICODE) : null,
            'tool_call_id' => $toolCallId,
            'tool_name'    => $toolName,
            'attachments'  => $attachments !== null ? json_encode($attachments, JSON_UNESCAPED_UNICODE) : null,
        ]);
        $this->touch($conversationId);
        return (int) $this->db->lastInsertId();
    }
}
