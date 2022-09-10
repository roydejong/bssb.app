<?php

namespace app\Controllers\Admin;

use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Enums\LobbyBanType;
use app\Models\LobbyBan;

class AdminBansController extends BaseAdminController
{
    public function getBans(Request $request): Response
    {
        if ($request->method === "POST") {
            $deleteBanId = intval($request->postParams['delete-ban'] ?? null);
            if ($deleteBanId > 0) {
                LobbyBan::query()
                    ->delete()
                    ->where('id = ?', $deleteBanId)
                    ->execute();
            }
            return new RedirectResponse('/admin/bans');
        }

        $bans = LobbyBan::query()
            ->orderBy('id DESC')
            ->queryAllModels();

        $view = new View('admin/bans.twig');
        $view->set('title', 'Lobby bans');
        $view->set('tab', 'bans');
        $view->set('bans', $bans);
        return $view->asResponse();
    }

    public function getBanItem(Request $request, string $id): Response
    {
        $ban = null;
        $creatingNew = false;

        if ($id === "new") {
            $ban = new LobbyBan();
            $creatingNew = true;
        } else {
            if (($id = intval($id)) > 0)
                $ban = LobbyBan::fetch($id);
            if (!$ban)
                return new RedirectResponse('/admin/bans?result=not_found');
        }

        $formData = array_merge(
            $ban->getPropertyValues(),
            $request->postParams
        );

        if ($request->method === "POST") {
            $ban->type = LobbyBanType::tryFrom($formData['type']);
            $ban->value = trim($formData['value']);
            $ban->comment = trim($formData['comment']);
            if (!empty($formData['expires']))
                $ban->expires = new \DateTime($formData['expires']);
            else
                $ban->expires = null;
            if (!isset($ban->created))
                $ban->created = new \DateTime('now');
            $ban->save();

            $resultCode = ($creatingNew ? 'created' : 'updated');
            return new RedirectResponse("/admin/bans?result={$resultCode}");
        }

        $view = new View('admin/ban_item.twig');
        $view->set('title', $creatingNew ? "New ban" : "Edit ban");
        $view->set('tab', 'bans');
        $view->set('changelog', $ban);
        $view->set('creatingNew', $creatingNew);
        $view->set('formData', $formData);
        $view->set('banTypes', LobbyBanType::cases());
        return $view->asResponse();
    }
}