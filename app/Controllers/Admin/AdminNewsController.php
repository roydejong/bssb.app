<?php

namespace app\Controllers\Admin;

use app\BSSB;
use app\External\Tweetinator;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;
use app\Models\Changelog;

class AdminNewsController extends BaseAdminController
{
    public function getNews(Request $request): Response
    {
        $changelogs = Changelog::query()
            ->orderBy('publish_date DESC')
            ->queryAllModels();

        $view = new View('admin/news.twig');
        $view->set('title', 'Newsfeed');
        $view->set('tab', 'news');
        $view->set('changelogs', $changelogs);
        return $view->asResponse();
    }

    public function getNewsItem(Request $request, string $id): Response
    {
        $changelog = null;
        $creatingNew = false;

        if ($id === "new") {
            $changelog = new Changelog();
            $creatingNew = true;
        } else {
            if (($id = intval($id)) > 0)
                $changelog = Changelog::fetch($id);
            if (!$changelog)
                return new RedirectResponse('/admin/news?result=not_found');
        }

        $wasAlert = $changelog->isAlert;

        $formData = array_merge(
            $changelog->getPropertyValues(),
            $request->postParams
        );

        if ($request->method === "POST") {
            $changelog->title = trim($formData['title']);
            $changelog->text = trim($formData['text']);
            if (empty($changelog->text))
                $changelog->text = null;
            $changelog->isAlert = $request->postParams['isAlert'] ?? 0 == 1;
            $changelog->isHidden = $request->postParams['isHidden'] ?? 0 == 1;
            if (empty($changelog->publishDate))
                $changelog->publishDate = new \DateTime('now');
            $changelog->save();

            if ($changelog->isAlert) {
                // Only one log can be marked as current alert
                Changelog::query()
                    ->update()
                    ->set(['is_alert' => 0])
                    ->where('id != ?', $changelog->id)
                    ->execute();

                // Update config
                $systemConfig = BSSB::getSystemConfig();
                $systemConfig->serverMessage = $changelog->title;
                $systemConfig->save();
            }

            if ($wasAlert && !$changelog->isAlert) {
                // Unmarked as alert, clear server message
                $systemConfig = BSSB::getSystemConfig();
                $systemConfig->serverMessage = null;
                $systemConfig->save();
            }

            $resultCode = ($creatingNew ? 'created' : 'updated');

            if ($creatingNew || empty($changelog->tweetId)) {
                $tweeter = new Tweetinator();
                $tweetId = $tweeter->postTweet($changelog->getTwitterText());

                if ($tweetId) {
                    $changelog->tweetId = $tweetId;
                    $changelog->save();
                } else {
                    $resultCode = "tweet_failed";
                }
            }

            return new RedirectResponse("/admin/news?result={$resultCode}");
        }

        $view = new View('admin/news_item.twig');
        $view->set('title', $creatingNew ? "Create news item" : "Edit news item");
        $view->set('tab', 'news');
        $view->set('changelog', $changelog);
        $view->set('creatingNew', $creatingNew);
        $view->set('formData', $formData);
        return $view->asResponse();
    }
}