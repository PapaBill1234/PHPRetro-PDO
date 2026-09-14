<?php
require_once __DIR__.'/PhpretroLiveSync.php';

class PhpretroGroupUrls
{
    public const MAX_LENGTH = 30;
    public const RESERVED = ['actions', 'id', 'discussions', 'home'];

    public function __construct(public Database $db, public PhpretroLiveSync $sync) {}

    public function normalize(string $alias): string
    {
        return trim($alias);
    }

    public function valid(string $alias): bool
    {
        global $input;
        $alias = $this->normalize($alias);
        if ($alias === '' || strlen($alias) > self::MAX_LENGTH) { return false; }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9-]{0,29}$/', $alias)) { return false; }
        if (in_array(strtolower($alias), self::RESERVED, true)) { return false; }
        if (ctype_digit($alias)) { return false; }
        return is_object($input) && $input->stringToURL($alias, false, false) === $alias;
    }

    public function taken(string $alias, int $exceptGuild = 0): bool
    {
        $alias = $this->normalize($alias);
        $row = $this->db->fetchRow('SELECT guild_id FROM phpretro_group_url_aliases WHERE alias = ?', [$alias]);
        if (!$row) { return false; }
        return $exceptGuild < 1 || (int) $row['guild_id'] !== $exceptGuild;
    }

    public function forGuild(int $guildId): string
    {
        if ($guildId < 1) { return ''; }
        return (string) ($this->db->fetchColumn('SELECT alias FROM phpretro_group_url_aliases WHERE guild_id = ?', [$guildId]) ?: '');
    }

    public function resolve(string $alias): ?int
    {
        $alias = $this->normalize($alias);
        if ($alias === '') { return null; }
        $id = $this->db->fetchColumn('SELECT guild_id FROM phpretro_group_url_aliases WHERE alias = ?', [$alias]);
        return $id ? (int) $id : null;
    }

    /** Claim once. Existing aliases are never changed. Does not write PolarIS guilds. */
    public function claim(int $guildId, int $ownerId, string $alias): string
    {
        $alias = $this->normalize($alias);
        $existing = $this->forGuild($guildId);
        if ($existing !== '') { return $existing; }
        if ($alias === '') { return ''; }
        if (!$this->valid($alias) || $this->taken($alias, $guildId)) {
            throw new InvalidArgumentException('This url name contains invalid characters or is already taken. It will not be saved.');
        }
        $owner = $this->db->fetchColumn('SELECT user_id FROM guilds WHERE id = ?', [$guildId]);
        if ((int) $owner !== $ownerId) { throw new InvalidArgumentException('Not permitted.'); }
        $this->db->execute('INSERT INTO phpretro_group_url_aliases (alias, guild_id, created_at) VALUES (?, ?, ?)', [$alias, $guildId, time()]);
        $this->sync->recordAndNotify('groups.alias_claimed', ['guild_id' => $guildId, 'alias' => $alias, 'user_id' => $ownerId]);
        return $alias;
    }
}

function phpretroGroupUrls(): PhpretroGroupUrls
{
    global $db;
    static $urls;
    if (!$urls) { $urls = new PhpretroGroupUrls($db, new PhpretroLiveSync($db)); }
    return $urls;
}

function phpretroGroupPath(int $id): string
{
    $alias = phpretroGroupUrls()->forGuild($id);
    return $alias !== '' ? PATH.'/groups/'.$alias : PATH.'/groups/'.$id.'/id';
}

function phpretroRequestGuildId(?array $get = null): int
{
    $get ??= $_GET;
    $id = isset($get['id']) && (is_int($get['id']) || is_string($get['id'])) ? (int) $get['id'] : 0;
    if ($id > 0) { return $id; }
    $alias = isset($get['alias']) && is_string($get['alias']) ? $get['alias'] : '';
    return phpretroGroupUrls()->resolve($alias) ?? 0;
}

function habbletGroupURL(int $id): string
{
    return phpretroGroupPath($id);
}
