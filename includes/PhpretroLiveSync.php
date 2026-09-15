<?php
/**
 * Website record vs live-game notify.
 *
 * record() writes phpretro_emulator_outbox. notifyLiveGame() is the hook a later
 * PolarIS worker plugs into. It is a no-op today: pending rows sit until a
 * consumer exists. Callers always record first, then notify.
 *
 * Website-owned features never write PolarIS live tables. Group purchase is the
 * documented exception: it mirrors PolarIS RequestGuildBuyEvent/createGuild.
 */
class PhpretroLiveSync
{
    public function __construct(private Database $db) {}

    public function record(string $eventType, array $payload): int
    {
        $this->db->execute(
            'INSERT INTO phpretro_emulator_outbox (event_type, payload_json) VALUES (?, ?)',
            [$eventType, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]
        );
        return (int) $this->db->insertId();
    }

    /** Future PolarIS outbox worker hook. Intentionally empty. */
    public function notifyLiveGame(int $outboxId): void
    {
        unset($outboxId);
    }

    public function recordAndNotify(string $eventType, array $payload): int
    {
        $id = $this->record($eventType, $payload);
        $this->notifyLiveGame($id);
        return $id;
    }
}
