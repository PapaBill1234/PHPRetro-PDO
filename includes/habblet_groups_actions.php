<?php
require_once __DIR__.'/habblet_groups.php';

function habbletForumText(string $text): string
{
    global $input;
    // The legacy formatter builds URLs, CSS and inline room JS from BBCode parameters.
    // Validate those parameters before preserving its original presentation markup.
    $text = preg_replace_callback('/\[(link|url|color|habbo|room|group)=([^\]]*)\]/is', static function ($match) {
        $tag = strtolower($match[1]); $value = $match[2];
        $valid = match ($tag) {
            'habbo', 'room', 'group' => preg_match('/\A[1-9][0-9]*\z/', $value),
            'color' => preg_match('/\A(?:#[0-9a-f]{6}|red|orange|yellow|green|cyan|blue|gray|black)\z/i', $value),
            default => preg_match('~\A(?:https?://|/)[^\x00-\x20<>"\x27\[\]\\\\]*\z~i', $value),
        };
        return $valid ? $match[0] : '';
    }, $text);
    return $input->bbcode_format(nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')));
}

function habbletForumList(HabbletGroups $groups, array $group, int $topicid): void
{
    global $lang;
    foreach (['groups.discussion', 'ajax.buttons', 'groups.discussion.showtopic', 'groups.discussion.topic'] as $locale) { $lang->addLocale($locale); }
    $threadrow = $groups->thread($group, $topicid);
    $total = (int) $groups->db->fetchColumn('SELECT COUNT(*) FROM guilds_forums_comments WHERE thread_id = ?', [$topicid]);
    $pages = max(1, (int) ceil($total / 10));
    $pagenum = habbletInt($_POST, 'page', 1);
    $pagenum = $pagenum === -1 ? $pages : max(1, min($pages, $pagenum));
    $comments = $groups->db->fetchAll('SELECT id, user_id, message, created_at, state FROM guilds_forums_comments WHERE thread_id = ? ORDER BY id LIMIT ? OFFSET ?', [$topicid, 10, ($pagenum - 1) * 10]);
    // Keep hidden rows in native pagination/counts, but never expose their message or edit payload.
    foreach ($comments as &$comment) {
        if (!in_array((int) $comment['state'], [0, 1], true)) { $comment['message'] = $lang->loc['post.deleted']; }
    }
    unset($comment);
    $firstid = (int) $groups->db->fetchColumn('SELECT id FROM guilds_forums_comments WHERE thread_id = ? ORDER BY id LIMIT 1', [$topicid]);
    $moderator = $groups->allowed($group, 'mod_forum');
    $canReply = $groups->canReply($group, $threadrow);
    $viewer = $groups->author($groups->actor);
    habbletGroupRender('forum-postlist', compact('groups', 'group', 'topicid', 'threadrow', 'total', 'pages', 'pagenum', 'comments', 'firstid', 'moderator', 'canReply', 'viewer'));
}

function habbletGroupDispatch(string $action): void
{
    habbletGroupRun(static function () use ($action) {
        global $user, $lang, $input, $page;
        $groups = new HabbletGroups(new Database(), (int) $user->id);
        $id = habbletInt($_POST, 'groupId');
        $lang->addLocale('ajax.buttons');
        if (in_array($action, ['startEditingSession', 'saveEditingSession', 'cancelEditingSession'], true)) {
            throw new HabbletGroupError('Group layout editing is unavailable: Polaris has no equivalent for legacy homes, item placement, or editing-session ownership.', 501);
        }
        if ($action === 'purchase') {
            throw new HabbletGroupError('Purchase groups in the game client. This legacy form does not supply the room, badge parts, colours or club/price policy required by Polaris.', 501);
        }
        if ($action === 'member-widget') {
            // homeview.js sends _groupspage.requested.group; PHP normalizes dots to underscores.
            $id = habbletInt($_POST, '_groupspage_requested_group');
        }
        $group = $groups->group($id);
        if (str_starts_with($action, 'forum-')) {
            $groups->forum($group);
            $topicid = habbletInt($_POST, 'topicId');
            $lang->addLocale('groups.discussion.topic');
            $lang->addLocale('groups.discussion.newtopic');
            if (in_array($action, ['forum-newtopic', 'forum-previewtopic', 'forum-previewpost'], true)) {
                $threadrow = $action === 'forum-previewpost' ? $groups->thread($group, $topicid) : null;
                $groups->need($threadrow ? $groups->canReply($group, $threadrow) : $groups->allowed($group, 'post_threads'));
                $author = $groups->author($groups->actor);
                $posts = $author['posts'];
                $message = $action === 'forum-newtopic' ? '' : habbletForumText($groups->text('message', 4000));
                $name = $action === 'forum-previewtopic' ? $input->HoloText($groups->text('topicName', 32)) : '';
                habbletGroupRender($action, compact('group', 'id', 'topicid', 'threadrow', 'author', 'posts', 'message', 'name'));
                return;
            }
            if ($action === 'forum-opentopicsettings') {
                $topicrow = $groups->thread($group, $topicid);
                $moderator = $groups->allowed($group, 'mod_forum');
                $groups->need($moderator || (int) $topicrow['opener_id'] === $groups->actor);
                $lang->addLocale('discussion.topicsettings');
                habbletGroupRender($action, compact('group', 'topicrow', 'moderator'));
                return;
            }
            $topicid = $groups->transaction(static function () use ($groups, $id, $topicid, $action) {
                $group = $groups->group($id, true);
                if ($action === 'forum-savetopic') { return $groups->post($group, null); }
                $thread = $groups->thread($group, $topicid, true);
                switch ($action) {
                    case 'forum-savepost': $groups->post($group, $thread); break;
                    case 'forum-updatepost': $groups->editPost($group, $thread, habbletInt($_POST, 'postId')); break;
                    case 'forum-savetopicsettings': $groups->topicSettings($group, $thread); break;
                    case 'forum-deletetopic': $groups->deleteTopic($group, $thread); break;
                    case 'forum-deletepost':
                        $groups->need($groups->allowed($group, 'mod_forum'));
                        $groups->need((bool) $groups->db->fetchColumn('SELECT id FROM guilds_forums_comments WHERE id = ? AND thread_id = ?', [habbletInt($_POST, 'postId'), $topicid]), 'Post not found in this topic.', 404);
                        throw new HabbletGroupError('Individual post deletion is unavailable: Polaris comment moderation state definitions conflict. No post was changed.', 501);
                    default: throw new LogicException('Unknown forum action.');
                }
                return $topicid;
            });
            if ($action === 'forum-savetopic') { echo habbletGroupURL($id).'/discussions/'.$topicid.'/id'; }
            elseif ($action === 'forum-deletetopic') { echo 'SUCCESS'; }
            else { habbletForumList($groups, $groups->group($id), $topicid); }
            return;
        }
        if (str_starts_with($action, 'members-')) {
            $confirm = str_starts_with($action, 'members-confirm-');
            $operation = substr($action, $confirm ? 16 : 8);
            $groups->need(in_array($operation, ['give_rights', 'revoke_rights'], true) ? $groups->owner($group) : $groups->admin($group));
            $targets = $groups->targets();
            $lang->addLocale('groups.members.batch');
            if ($confirm) { habbletGroupRender($action, compact('group', 'targets')); }
            else {
                $groups->transaction(static function () use ($groups, $id, $operation, $targets) {
                    $groups->memberAction($groups->group($id, true), $operation, $targets);
                });
                echo 'OK';
            }
            return;
        }
        if (in_array($action, ['memberlist', 'member-widget'], true)) {
            $pending = $action === 'memberlist' && habbletText($_POST, 'pending') === 'true';
            if ($action === 'memberlist') { $groups->need($groups->admin($group)); }
            $lang->addLocale('homes.widget.groups');
            $lang->addLocale($action === 'memberlist' ? 'groups.settings.members' : 'memberswidget.memberslist');
            $search = habbletText($_POST, 'searchString');
            // LOCATE treats wildcard characters literally and binds the complete search value.
            $filter = $pending ? 'm.level_id = 3' : 'm.level_id IN (0, 1, 2)';
            $countSearch = (int) $groups->db->fetchColumn('SELECT COUNT(*) FROM guilds_members m INNER JOIN users u ON u.id = m.user_id WHERE m.guild_id = ? AND '.$filter.' AND LOCATE(?, u.username) > 0', [$id, $search]);
            $limit = $action === 'member-widget' ? 20 : ($pending ? 12 : 0);
            $totalpages = $limit ? max(1, (int) ceil($countSearch / $limit)) : 1;
            $pagenum = max(1, min($totalpages, habbletInt($_POST, 'pageNumber', 1)));
            $offset = $limit * ($pagenum - 1);
            $sql = 'SELECT m.user_id, m.level_id, u.username, u.look, u.account_created, CASE WHEN s.hide_online = \'1\' THEN \'0\' ELSE u.online END AS online, COALESCE(s.guild_id, 0) AS favorite_id FROM guilds_members m INNER JOIN users u ON u.id = m.user_id LEFT JOIN users_settings s ON s.user_id = u.id WHERE m.guild_id = ? AND '.$filter.' AND LOCATE(?, u.username) > 0 ORDER BY u.username, m.id';
            $params = [$id, $search];
            if ($limit) { $sql .= ' LIMIT ? OFFSET ?'; array_push($params, $limit, $offset); }
            $members = $groups->db->fetchAll($sql, $params);
            if ($action === 'memberlist') {
                $count = ['search' => $countSearch,
                    'pending' => (int) $groups->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = ? AND level_id = 3', [$id]),
                    'total' => (int) $groups->db->fetchColumn('SELECT COUNT(*) FROM guilds_members WHERE guild_id = ? AND level_id IN (0, 1, 2)', [$id])];
                header('X-JSON: '.json_encode(['pending' => $lang->loc['pending.members'].' ('.$count['pending'].')', 'members' => $lang->loc['members'].' ('.$count['total'].')']));
            } else { $count = $countSearch; }
            $widgetid = max(0, habbletInt($_POST, 'widgetId'));
            $groupid = $id; $bypass = false;
            habbletGroupRender($action, compact('group', 'count', 'members', 'pagenum', 'offset', 'limit', 'search', 'totalpages', 'widgetid', 'groupid', 'bypass'));
            return;
        }
        if ($action === 'groupinfo') {
            $lang->addLocale('homes.widget.groups');
            $ownerid = habbletInt($_POST, 'ownerId');
            $groups->need($ownerid > 0, 'Invalid profile owner.', 400);
            $rank = $groups->rank($id, $ownerid);
            $favorite = (int) $groups->db->fetchColumn('SELECT guild_id FROM users_settings WHERE user_id = ?', [$ownerid]) === $id;
            habbletGroupRender('groupinfo', compact('group', 'ownerid', 'rank', 'favorite'));
            return;
        }
        if (in_array($action, ['show_badge_editor', 'update_group_badge'], true)) {
            $groups->need($groups->owner($group));
            throw new HabbletGroupError('Use the game client to edit group badges. The legacy Flash editor uses a different part encoding from Polaris.', 501);
        }
        if ($action === 'check_group_url') {
            $groups->need($groups->owner($group));
            $lang->addLocale('groups.settings.checkurl');
            $url = habbletText($_POST, 'url');
            $urls = phpretroGroupUrls();
            if (!$urls->valid($url) || $urls->taken($url, $id)) {
                echo 'ERROR '.$lang->loc['url.error'];
                return;
            }
            echo $lang->loc['your.alias'].': '.PATH.'/groups/'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'. '.$lang->loc['you.cannot.alter'];
            return;
        }
        if ($action === 'group_settings') {
            $groups->need($groups->owner($group));
            $groups->need($group['read_forum'] !== 'ADMINS' && $group['post_threads'] !== 'OWNER' && (int) $group['state'] !== 4, 'These Polaris settings cannot be represented by the legacy form. Use the game client.', 501);
            $lang->addLocale('groups.settings');
            $alias = phpretroGroupUrls()->forGuild($id);
            $noalias = ($alias === '');
            $readOption = $group['read_forum'] === 'EVERYONE' ? 0 : 1;
            $postOption = array_search($group['post_threads'], ['EVERYONE', 'MEMBERS', 'ADMINS'], true);
            $rooms = $groups->db->fetchAll('SELECT id, name, description FROM rooms WHERE owner_id = ? ORDER BY id', [$groups->actor]);
            habbletGroupRender('group-settings', compact('group', 'alias', 'noalias', 'readOption', 'postOption', 'rooms'));
            return;
        }
        if ($action === 'confirm_delete_group') {
            $groups->need($groups->owner($group)); $lang->addLocale('groups.requests.delete');
            habbletGroupRender($action, compact('group')); return;
        }
        if ($action === 'confirm_select_favorite') {
            $groups->need(habbletInt($_POST, 'targetAccountId') === $groups->actor && ($groups->owner($group) || in_array($groups->rank($id), [0, 1, 2], true)));
            $lang->addLocale('groups.requests.favorite');
            habbletGroupRender($action, compact('group')); return;
        }
        $result = $groups->transaction(static function () use ($groups, $id, $action) {
            $group = $groups->group($id, true);
            switch ($action) {
                case 'join': return $groups->join($group);
                case 'leave': $groups->removeMember($group, $groups->actor); break;
                case 'select_favorite': $groups->favorite($group, true); break;
                case 'deselect_favorite': $groups->favorite($group, false); break;
                case 'update_group_settings': $groups->settings($group); break;
                case 'delete_group': $groups->deleteGroup($group); break;
                default: throw new LogicException('Unknown group action.');
            }
            return null;
        });
        if ($action === 'join') {
            $lang->addLocale('groups.requests.join');
            echo '<p>'.($result ? $lang->loc['membership.request.sent'] : $lang->loc['joined.group']).'</p><p><a href="#" class="new-button" id="group-action-ok"><b>'.$lang->loc['ok'].'</b><i></i></a></p><div class="clear"></div>';
        } elseif ($action === 'leave') {
            echo '<script type="text/javascript">location.href = '.json_encode(habbletGroupURL($id)).';</script>';
        } elseif ($action === 'update_group_settings') {
            $lang->addLocale('groups.settings.save');
            echo $lang->loc['saved.successful'].'<p><a href="'.habbletGroupURL($id).'" class="new-button"><b>'.$lang->loc['done'].'</b><i></i></a></p><div class="clear"></div>';
        } elseif ($action === 'delete_group') {
            $lang->addLocale('groups.requests.delete');
            echo '<p>'.$lang->loc['deleted'].'</p><p><a href="'.PATH.'/me" class="new-button" id="group-action-ok"><b>'.$lang->loc['ok'].'</b><i></i></a></p><div class="clear"></div>';
        } else { echo 'OK'; }
    });
}
