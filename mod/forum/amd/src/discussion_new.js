// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module for viewing a discussion.
 *
 * @module     mod_forum/discussion_new
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import AutoRows from 'core/auto_rows';
import CustomEvents from 'core/custom_interaction_events';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Discussion from 'mod_forum/discussion';
import InPageReply from 'mod_forum/inpage_reply';
import LockToggle from 'mod_forum/lock_toggle';
import FavouriteToggle from 'mod_forum/favourite_toggle';
import Pin from 'mod_forum/pin_toggle';
import Selectors from 'mod_forum/selectors';

const ANIMATION_DURATION = 150;

const clearUrlHash = () => {
    // After adding a reply a url hash is being generated that scrolls (points) to the newly added reply.
    // The hash being present causes this scrolling behavior to the particular reply to persists even when
    // another, non-related in-page replay link is being clicked which ultimately causes a bad user experience.
    // A particular solution for this problem would be changing the browser's history state when a url hash is
    // present.
    if (window.location.hash) {
        // Remove the fragment identifier from the url.
        var url = window.location.href.split('#')[0];
        history.pushState({}, document.title, url);
    }
};

const getPostContainer = (element) => {
    return element.closest(Selectors.post.post);
};

const getPostContentContainer = (postContainer) => {
    return postContainer.children().not(Selectors.post.repliesContainer).find(Selectors.post.forumCoreContent);
};

const getInPageReplyContainer = (postContainer) => {
    return postContainer.children().filter(Selectors.post.inpageReplyContainer);
};

const showInPageReplyForm = (container, afterAnimationCallback) => {
    const form = container.find(Selectors.post.inpageReplyContent);

    form.slideDown({
        duration: ANIMATION_DURATION,
        queue: false,
        complete: afterAnimationCallback
    }).css('display', 'none').fadeIn(ANIMATION_DURATION);
};

const hideInPageReplyForm = (container, afterAnimationCallback) => {
    const form = container.find(Selectors.post.inpageReplyContent);

    form.slideUp({
        duration: ANIMATION_DURATION,
        queue: false,
        complete: afterAnimationCallback
    }).fadeOut(200);
};

const hasInPageReplyForm = (inPageReplyContainer) => {
    return inPageReplyContainer.find(Selectors.post.inpageReplyContent).length > 0;
};

const renderInPageReplyTemplate = (additionalTemplateContext, button, postContainer) => {
    const postContentContainer = getPostContentContainer(postContainer);
    const currentSubject = postContentContainer.find(Selectors.post.forumSubject).text();
    const currentAuthorName = postContentContainer.find(Selectors.post.authorName).text();
    const context = {
        postid: postContainer.data('post-id'),
        "reply_url": button.attr('data-href'),
        sesskey: M.cfg.sesskey,
        parentsubject: currentSubject,
        parentauthorname: currentAuthorName,
        canreplyprivately: button.data('can-reply-privately'),
        postformat: InPageReply.CONTENT_FORMATS.MOODLE,
        ...additionalTemplateContext
    };

    return Templates.render('mod_forum/inpage_reply_new', context);
};

const registerEventListeners = (root, additionalTemplateContext) => {
    CustomEvents.define(root, [CustomEvents.events.activate]);
    AutoRows.init(root);

    root.on(CustomEvents.events.activate, Selectors.post.inpageReplyCreateButton, async (e, data) => {
        data.originalEvent.preventDefault();

        clearUrlHash();

        const button = $(e.currentTarget);
        const postContainer = getPostContainer(button);
        const inPageReplyContainer = getInPageReplyContainer(postContainer);

        if (!hasInPageReplyForm(inPageReplyContainer)) {
            try {
                const html = await renderInPageReplyTemplate(additionalTemplateContext, button, postContainer);
                Templates.appendNodeContents(inPageReplyContainer, html, '');
            } catch (e) {
                Notification.exception(e);
            }
        }

        button.fadeOut(ANIMATION_DURATION, () => {
            showInPageReplyForm(inPageReplyContainer, () => {
                inPageReplyContainer.find(Selectors.post.inpageReplyContent).find('textarea').focus();
            });
        });

        return;
    });

    root.on(CustomEvents.events.activate, Selectors.post.inpageReplyCancelButton, (e, data) => {
        data.originalEvent.preventDefault();

        const button = $(e.currentTarget);
        const postContainer = getPostContainer(button);
        const postContentContainer = getPostContentContainer(postContainer);
        const inPageReplyContainer = getInPageReplyContainer(postContainer);
        const inPageReplyCreateButton = postContentContainer.find(Selectors.post.inpageReplyCreateButton);
        hideInPageReplyForm(inPageReplyContainer, () => {
            inPageReplyCreateButton.fadeIn(ANIMATION_DURATION);
        });
    });
};

export const init = (root, context) => {
    registerEventListeners(root, context);
    Discussion.init(root);
    InPageReply.init(root);

    const discussionToolsContainer = root.find('[data-container="discussion-tools"]');
    LockToggle.init(discussionToolsContainer);
    FavouriteToggle.init(discussionToolsContainer);
    Pin.init(discussionToolsContainer);
};
