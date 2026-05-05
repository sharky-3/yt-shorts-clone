// ── [[ Variables ]] ────────────────────────────────────────────────────

let offset  = 0;
let loading = false;
let done    = false;

const feed = document.getElementById('feed');
const tpl  = document.getElementById('short-tpl');

// ── [[ Auto Play / Pause ]] ────────────────────────────────────────────────────

const playObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        const vid = entry.target.querySelector('video');
        if (!vid) return;
        if (entry.isIntersecting) {
            vid.play().catch(() => {});
        } else {
            vid.pause();
        }
    });
}, { threshold: 0.7 });

// ── [[ Infinitive Scroll ]] ────────────────────────────────────────────────────

const scrollObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) loadMore();
}, { threshold: 0.1 });

const sentinel = document.createElement('div');
sentinel.style.cssText = 'height:1px;flex-shrink:0;';
feed.appendChild(sentinel);
scrollObserver.observe(sentinel);

// ── [[ Load Videos ]] ────────────────────────────────────────────────────

async function loadMore() {
    if (loading || done) return;
    loading = true;

    const res  = await fetch(`/feed.php?offset=${offset}`);
    const vids = await res.json();

    if (!vids.length) { done = true; loading = false; return; }

    vids.forEach(v => {
        const node = tpl.content.cloneNode(true);
        const wrap = node.querySelector('.short');

        wrap.dataset.id = v.id;

        // ── [[ Video ]] ────────────────────────────────────────────────────
        const video = wrap.querySelector('video');
        video.src = `/stream.php?f=${encodeURIComponent(v.filename)}`;
        video.addEventListener('click', () => video.paused ? video.play() : video.pause());

        // ── [[ Meta ]] ────────────────────────────────────────────────────
        wrap.querySelector('.short-author').href        = `/profile.php?id=${v.author_id}`;
        wrap.querySelector('.short-author').textContent = `@${v.username}`;
        wrap.querySelector('.short-title').textContent  = v.title;
        wrap.querySelector('.short-views').textContent  = `${Number(v.views).toLocaleString()} views`;

        // ── [[ Like Button ]] ────────────────────────────────────────────────────
        const likeBtn = wrap.querySelector('.like-btn');
        likeBtn.dataset.id = v.id;
        likeBtn.querySelector('.like-count').textContent = v.like_count;
        if (Number(v.liked_by_me)) likeBtn.classList.add('liked');
        likeBtn.addEventListener('click', () => toggleLike(likeBtn, v.id));

        // ── [[ Comment ]] ────────────────────────────────────────────────────
        const commentToggle = wrap.querySelector('.comment-toggle');
        const commentsPanel = wrap.querySelector('.comments-panel');
        if (commentToggle) {
            commentToggle.dataset.id = v.id;
            commentToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleComments(commentsPanel, v.id);
            });
        }

        const commentForm = wrap.querySelector('.comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const input = commentForm.querySelector('input');
                const body  = input.value.trim();
                if (!body) return;
                await postComment(v.id, body, wrap.querySelector('.comments-list'));
                input.value = '';
            });
        }

        feed.insertBefore(node, sentinel);
        playObserver.observe(wrap);
    });

    offset += vids.length;
    loading = false;

    checkDeepLink();
}

// ── [[ Deep Link / Scroll To ? ]] ────────────────────────────────────────────────────

let deepLinkDone = false;
function checkDeepLink() {
    if (deepLinkDone) return;
    const targetId = new URLSearchParams(location.search).get('v');
    if (!targetId) { deepLinkDone = true; return; }

    const shorts = [...document.querySelectorAll('.short')];
    const idx    = shorts.findIndex(s => s.dataset.id == targetId);
    if (idx !== -1) {
        const itemH = feed.clientHeight;
        feed.scrollTo({ top: idx * itemH, behavior: 'instant' });
        deepLinkDone = true;
    }
}

// ── [[ Like / UnLike ]] ────────────────────────────────────────────────────

async function toggleLike(btn, videoId) {
    if (!LOGGED_IN) { window.location = '/auth.php'; return; }

    const res  = await fetch('/like.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'video_id=' + videoId,
    });
    const data = await res.json();
    btn.querySelector('.like-count').textContent = data.likes;
    btn.classList.toggle('liked', data.liked);
}

// ── [[ Comments ]] ────────────────────────────────────────────────────

async function toggleComments(panel, videoId) {
    const isOpen = panel.style.display === 'block';

    if (isOpen) {
        panel.style.display = 'none';
        return;
    }

    panel.style.display = 'block';

    if (!panel.querySelector('.comments-panel-header')) {
        const header = document.createElement('div');
        header.className = 'comments-panel-header';
        header.innerHTML = `
            <span style="font-weight:600;font-size:14px">Comments</span>
            <button class="comments-close" title="Close">✕</button>`;
        header.querySelector('.comments-close').addEventListener('click', () => {
            panel.style.display = 'none';
        });
        panel.prepend(header);
    }

    const list = panel.querySelector('.comments-list');
    list.innerHTML = '<p style="color:#aaa;font-size:13px;padding:8px 0">Loading…</p>';

    const res  = await fetch(`/comment.php?video_id=${videoId}`);
    const data = await res.json();
    renderComments(list, data);

    setTimeout(() => {
        document.addEventListener('click', function outsideClick(e) {
            if (!panel.contains(e.target)) {
                panel.style.display = 'none';
                document.removeEventListener('click', outsideClick);
            }
        });
    }, 100);
}

async function postComment(videoId, body, list) {
    if (!LOGGED_IN) { window.location = '/auth.php'; return; }

    const res     = await fetch('/comment.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    `video_id=${videoId}&body=${encodeURIComponent(body)}`,
    });
    const comment = await res.json();
    if (!comment.error) renderComments(list, [comment], true);
}

function renderComments(list, comments, prepend = false) {
    if (!comments.length && !prepend) {
        list.innerHTML = '<p style="color:#aaa;font-size:13px;padding:8px 0">No comments yet.</p>';
        return;
    }
    if (!prepend) list.innerHTML = '';
    comments.forEach(c => {
        const el = document.createElement('div');
        el.className = 'comment-item';
        el.innerHTML = `<b>@${escHtml(c.username)}</b><p>${escHtml(c.body)}</p>`;
        prepend ? list.prepend(el) : list.appendChild(el);
    });
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ── [[ KeyBoard Navigator ]] ────────────────────────────────────────────────────

document.addEventListener('keydown', (e) => {
    const shorts = [...document.querySelectorAll('.short')];
    if (!shorts.length) return;

    const itemH   = feed.clientHeight;
    const current = Math.round(feed.scrollTop / itemH);

    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
        e.preventDefault();
        const next = Math.min(current + 1, shorts.length - 1);
        feed.scrollTo({ top: next * itemH, behavior: 'smooth' });
    }
    if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
        e.preventDefault();
        const prev = Math.max(current - 1, 0);
        feed.scrollTo({ top: prev * itemH, behavior: 'smooth' });
    }
});
