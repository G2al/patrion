<div
    class="patrion-ai-root"
    x-data="{
        open: @js($isOpen),
        working: false,
        draft: '',
        optimistic: '',
        queued: [],
        async submit(question = null) {
            const text = String(question ?? this.draft).trim()
            if (! text) return

            if (this.working) {
                this.queued.push(text)
                this.draft = ''
                this.$nextTick(() => this.scroll())
                return
            }

            await this.run(text)
        },
        async run(text) {
            if (! text) return

            this.optimistic = text
            this.draft = ''
            this.working = true
            this.$nextTick(() => this.scroll())

            try {
                await this.$wire.sendMessage(text)
            } finally {
                this.working = false
                this.optimistic = ''
                this.$nextTick(() => {
                    this.resize()
                    this.scroll()
                    this.$refs.input?.focus()
                })

                if (this.queued.length) {
                    const next = this.queued.shift()
                    this.$nextTick(() => this.run(next))
                }
            }
        },
        resize() {
            const input = this.$refs.input
            if (! input) return
            input.style.height = 'auto'
            input.style.height = Math.min(input.scrollHeight, 128) + 'px'
        },
        scroll() {
            const messages = this.$refs.messages
            if (messages) messages.scrollTop = messages.scrollHeight
        },
    }"
    x-on:ai-scroll.window="$nextTick(() => scroll())"
>
    <style>
        .patrion-ai-root { --ai-accent: #f59e0b; --ai-ink: #7c3f00; --ai-muted: #8a6a45; --ai-line: #f0c27b; --ai-surface: #fffaf2; --ai-soft: #fff1d6; position: relative; z-index: 60; }
        .dark .patrion-ai-root { --ai-ink: #f8fafc; --ai-muted: #aab3c2; --ai-line: #303949; --ai-surface: #111827; --ai-soft: #1b2535; }
        .patrion-ai-launcher { position: fixed; right: 1.5rem; bottom: 1.5rem; display: grid; width: 3.5rem; height: 3.5rem; place-items: center; border: 1px solid rgb(255 255 255 / .72); border-radius: 999px; background: var(--ai-ink); color: var(--ai-surface); box-shadow: 0 14px 36px rgb(15 23 42 / .24); cursor: pointer; transition: transform .18s ease, box-shadow .18s ease; }
        .dark .patrion-ai-launcher { border-color: rgb(245 158 11 / .45); background: #d97706; color: #fff7ed; }
        .patrion-ai-launcher:hover { transform: translateY(-2px); box-shadow: 0 18px 42px rgb(15 23 42 / .3); }
        .patrion-ai-launcher:focus-visible { outline: 3px solid rgb(245 158 11 / .3); outline-offset: 3px; }
        .patrion-ai-launcher-status { position: absolute; right: .08rem; bottom: .22rem; width: .72rem; height: .72rem; border: 2px solid var(--ai-surface); border-radius: 999px; background: #22c55e; }
        .patrion-ai-window { position: fixed; right: 1.5rem; bottom: 5.85rem; display: flex; width: min(28rem, calc(100vw - 2rem)); height: min(41rem, calc(100dvh - 7.5rem)); min-height: 29rem; flex-direction: column; overflow: hidden; border: 1px solid var(--ai-line); border-radius: 1.15rem; background: var(--ai-surface); color: var(--ai-ink); box-shadow: 0 26px 80px rgb(15 23 42 / .24), 0 4px 16px rgb(15 23 42 / .1); transform-origin: bottom right; will-change: transform, opacity; }
        .patrion-ai-window-transition { transition: opacity .24s cubic-bezier(.16, 1, .3, 1), transform .3s cubic-bezier(.16, 1, .3, 1); }
        .patrion-ai-window-hidden { opacity: 0; transform: translateY(.9rem) scale(.965); }
        .patrion-ai-window-visible { opacity: 1; transform: translateY(0) scale(1); }
        .patrion-ai-header { display: flex; min-height: 3.85rem; align-items: center; gap: .7rem; padding: .65rem .75rem .65rem .85rem; border-bottom: 1px solid rgb(255 255 255 / .16); background: linear-gradient(135deg, #c26100, #9f4800); color: #fffaf2; }
        .patrion-ai-avatar { position: relative; display: grid; width: 2.35rem; height: 2.35rem; flex: 0 0 auto; place-items: center; border-radius: .75rem; background: var(--ai-ink); color: var(--ai-surface); }
        .dark .patrion-ai-avatar { background: #f8fafc; color: #172033; box-shadow: 0 4px 14px rgb(0 0 0 / .22); }
        .patrion-ai-avatar::after { position: absolute; right: -.08rem; bottom: -.08rem; width: .58rem; height: .58rem; border: 2px solid var(--ai-surface); border-radius: 999px; background: #22c55e; content: ''; }
        .patrion-ai-heading { min-width: 0; flex: 1; }
        .patrion-ai-heading strong { display: block; font-size: .875rem; font-weight: 700; line-height: 1.2; letter-spacing: -.01em; }
        .patrion-ai-heading span { display: block; margin-top: .13rem; color: rgb(255 250 242 / .76); font-size: .68rem; }
        .patrion-ai-header-actions { display: flex; align-items: center; gap: .1rem; }
        .patrion-ai-icon-button { display: grid; width: 2rem; height: 2rem; place-items: center; border-radius: .58rem; color: rgb(255 250 242 / .82); transition: background .15s ease, color .15s ease; }
        .patrion-ai-icon-button:hover { background: rgb(255 255 255 / .14); color: #fff; }
        .patrion-ai-content { position: relative; display: flex; min-height: 0; flex: 1; flex-direction: column; }
        .patrion-ai-history { position: absolute; inset: 0; z-index: 5; overflow-y: auto; background: var(--ai-surface); padding: .9rem; }
        .patrion-ai-history-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
        .patrion-ai-history-title { font-size: .72rem; font-weight: 750; letter-spacing: .07em; text-transform: uppercase; }
        .patrion-ai-history-list { display: flex; flex-direction: column; gap: .25rem; }
        .patrion-ai-history-item { display: flex; align-items: center; gap: .2rem; border-radius: .7rem; color: var(--ai-muted); }
        .patrion-ai-history-item:hover, .patrion-ai-history-item.is-active { background: var(--ai-soft); color: var(--ai-ink); }
        .patrion-ai-history-item.is-active { box-shadow: inset 3px 0 var(--ai-accent); }
        .patrion-ai-history-select { min-width: 0; flex: 1; overflow: hidden; padding: .65rem .75rem; text-align: left; text-overflow: ellipsis; white-space: nowrap; font-size: .78rem; }
        .patrion-ai-history-delete { padding: .5rem; opacity: .45; }
        .patrion-ai-history-delete:hover { color: #ef4444; opacity: 1; }
        .patrion-ai-messages { min-height: 0; flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 1.1rem 1rem .7rem; scroll-behavior: smooth; scrollbar-color: color-mix(in srgb, var(--ai-muted) 38%, transparent) transparent; scrollbar-width: thin; }
        .patrion-ai-empty { display: grid; min-height: 100%; place-items: center; padding: 1rem .3rem; text-align: center; }
        .patrion-ai-empty-mark { display: inline-grid; width: 2.8rem; height: 2.8rem; place-items: center; border: 1px solid var(--ai-line); border-radius: .9rem; background: var(--ai-soft); color: var(--ai-ink); box-shadow: 0 4px 14px rgb(15 23 42 / .06); }
        .patrion-ai-empty h3 { margin-top: .75rem; font-size: .96rem; font-weight: 750; letter-spacing: -.015em; }
        .patrion-ai-empty p { margin: .25rem auto 0; max-width: 20rem; color: var(--ai-muted); font-size: .75rem; line-height: 1.45; }
        .patrion-ai-suggestions { display: grid; gap: .42rem; width: 100%; margin-top: 1.15rem; }
        .patrion-ai-suggestion { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: .7rem; padding: .65rem .72rem; border: 1px solid var(--ai-line); border-radius: .72rem; background: var(--ai-surface); color: var(--ai-ink); text-align: left; font-size: .75rem; transition: border-color .15s ease, background .15s ease, transform .15s ease; }
        .patrion-ai-suggestion:hover { border-color: rgb(245 158 11 / .65); background: color-mix(in srgb, var(--ai-accent) 6%, var(--ai-surface)); transform: translateX(2px); }
        .patrion-ai-suggestion svg { width: .9rem; flex: 0 0 auto; color: var(--ai-muted); }
        .patrion-ai-message { display: flex; align-items: flex-end; gap: .48rem; margin-bottom: .9rem; animation: patrion-ai-message-in .28s cubic-bezier(.16, 1, .3, 1) both; }
        .patrion-ai-message.is-user { justify-content: flex-end; }
        .patrion-ai-assistant-mark { display: grid; width: 1.55rem; height: 1.55rem; flex: 0 0 auto; place-items: center; border: 1px solid var(--ai-line); border-radius: .5rem; background: var(--ai-soft); color: var(--ai-ink); }
        .patrion-ai-bubble { max-width: 83%; font-size: .8rem; line-height: 1.58; overflow-wrap: anywhere; }
        .patrion-ai-message.is-user .patrion-ai-bubble { display: inline-flex; width: auto; height: auto !important; min-height: 0 !important; max-width: 74%; flex-direction: column; align-items: stretch; gap: .3rem; padding: .58rem .72rem .48rem; border-radius: .9rem .9rem .24rem .9rem; background: var(--ai-ink); color: var(--ai-surface); line-height: 1.42; white-space: normal; }
        .patrion-ai-user-text { display: block; margin: 0; padding: 0; text-align: left; white-space: pre-wrap; }
        .dark .patrion-ai-message.is-user .patrion-ai-bubble { background: linear-gradient(135deg, #d97706, #b45309); color: #fff; box-shadow: 0 5px 16px rgb(0 0 0 / .18); }
        .patrion-ai-message.is-assistant .patrion-ai-bubble { max-width: calc(100% - 2.05rem); padding: .48rem .7rem; border: 1px solid var(--ai-line); border-radius: .2rem .85rem .85rem .85rem; background: var(--ai-soft); color: var(--ai-ink); }
        .patrion-ai-streaming-preview { min-height: 1.25rem; white-space: pre-wrap; overflow-wrap: anywhere; }
        .patrion-ai-bubble p + p, .patrion-ai-bubble ul, .patrion-ai-bubble ol { margin-top: .5rem; }
        .patrion-ai-bubble ul { list-style: disc; padding-left: 1.05rem; }
        .patrion-ai-bubble ol { list-style: decimal; padding-left: 1.05rem; }
        .patrion-ai-bubble li + li { margin-top: .3rem; }
        .patrion-ai-bubble a { color: #b45309; font-weight: 650; text-decoration: underline; text-decoration-color: rgb(180 83 9 / .35); text-underline-offset: 2px; }
        .dark .patrion-ai-bubble a { color: #fbbf24; }
        .patrion-ai-time { margin-top: .25rem; color: var(--ai-muted); font-size: .59rem; line-height: 1; opacity: .75; }
        .patrion-ai-message.is-user .patrion-ai-time { align-self: flex-end; margin: 0; color: inherit; opacity: .55; text-align: right; }
        .patrion-ai-thinking { display: flex; align-items: center; gap: .45rem; margin: -.15rem 0 .9rem 2.05rem; color: var(--ai-muted); font-size: .68rem; }
        .patrion-ai-thinking-dots { display: flex; gap: .2rem; }
        .patrion-ai-dot { width: .28rem; height: .28rem; border-radius: 999px; background: var(--ai-accent); animation: patrion-ai-pulse 1s infinite alternate; }
        .patrion-ai-dot:nth-child(2) { animation-delay: .18s; }
        .patrion-ai-dot:nth-child(3) { animation-delay: .36s; }
        @keyframes patrion-ai-pulse { to { opacity: .22; transform: translateY(-2px); } }
        @keyframes patrion-ai-message-in { from { opacity: 0; transform: translateY(.4rem); } to { opacity: 1; transform: translateY(0); } }
        .patrion-ai-composer { border-top: 1px solid var(--ai-line); background: var(--ai-surface); padding: .72rem .78rem .68rem; }
        .patrion-ai-compose-shell { display: flex; align-items: flex-end; gap: .45rem; padding: .35rem .38rem .35rem .72rem; border: 1px solid var(--ai-line); border-radius: .9rem; background: var(--ai-soft); transition: border-color .15s ease, box-shadow .15s ease, background .15s ease; }
        .patrion-ai-compose-shell:focus-within { border-color: rgb(245 158 11 / .7); background: var(--ai-surface); box-shadow: 0 0 0 3px rgb(245 158 11 / .1); }
        .patrion-ai-textarea { min-height: 2.2rem; max-height: 8rem; width: 100%; resize: none; border: 0; background: transparent; padding: .45rem 0 .35rem; color: var(--ai-ink); font-size: .8rem; line-height: 1.4; outline: none; }
        .patrion-ai-textarea::placeholder { color: var(--ai-muted); }
        .patrion-ai-textarea:disabled { cursor: wait; opacity: .6; }
        .patrion-ai-send { display: grid; width: 2.25rem; height: 2.25rem; flex: 0 0 auto; place-items: center; border-radius: .68rem; background: var(--ai-accent); color: #3a1d00; transition: transform .15s ease, opacity .15s ease; }
        .patrion-ai-send:hover:not(:disabled) { transform: scale(1.04); }
        .patrion-ai-send:disabled { cursor: wait; opacity: .38; }
        .patrion-ai-help { display: flex; align-items: center; gap: .3rem; margin-top: .42rem; padding: 0 .15rem; color: var(--ai-muted); font-size: .59rem; }
        .patrion-ai-help-dot { width: .3rem; height: .3rem; border-radius: 999px; background: #22c55e; }
        .patrion-ai-error { margin-top: .35rem; font-size: .68rem; color: #dc2626; }
        [x-cloak] { display: none !important; }
        @media (max-width: 540px) {
            .patrion-ai-launcher { right: 1rem; bottom: 1rem; }
            .patrion-ai-window { right: 0; bottom: 0; width: 100vw; height: min(46rem, 92dvh); min-height: 25rem; border-right: 0; border-bottom: 0; border-left: 0; border-radius: 1.2rem 1.2rem 0 0; transform-origin: bottom center; }
            .patrion-ai-messages { padding-inline: .85rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .patrion-ai-launcher, .patrion-ai-suggestion, .patrion-ai-send, .patrion-ai-messages, .patrion-ai-window-transition { transition: none; scroll-behavior: auto; }
            .patrion-ai-dot, .patrion-ai-message { animation: none; }
        }
    </style>

        <section
            class="patrion-ai-window"
            role="dialog"
            aria-modal="false"
            aria-label="Assistente AI Patrion"
            x-cloak
            x-show="open"
            x-on:keydown.escape.window="open = false"
            x-transition:enter="patrion-ai-window-transition"
            x-transition:enter-start="patrion-ai-window-hidden"
            x-transition:enter-end="patrion-ai-window-visible"
            x-transition:leave="patrion-ai-window-transition"
            x-transition:leave-start="patrion-ai-window-visible"
            x-transition:leave-end="patrion-ai-window-hidden"
        >
            <header class="patrion-ai-header">
                <div class="patrion-ai-avatar">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4" />
                </div>
                <div class="patrion-ai-heading">
                    <strong>Assistente Patrion</strong>
                    <span>Connesso al tuo CRM · sola lettura</span>
                </div>
                <div class="patrion-ai-header-actions">
                    <button type="button" wire:click="toggleHistory" class="patrion-ai-icon-button" aria-label="Cronologia" title="Cronologia">
                        <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4" />
                    </button>
                    <button type="button" wire:click="newConversation" class="patrion-ai-icon-button" aria-label="Nuova conversazione" title="Nuova conversazione">
                        <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                    </button>
                    <button type="button" x-on:click="open = false" class="patrion-ai-icon-button" aria-label="Chiudi" title="Chiudi">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>
            </header>

            <div class="patrion-ai-content">
                @if ($showHistory)
                    <div class="patrion-ai-history">
                        <div class="patrion-ai-history-top">
                            <span class="patrion-ai-history-title">Conversazioni</span>
                            <button type="button" wire:click="toggleHistory" class="patrion-ai-icon-button" aria-label="Torna alla chat">
                                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="patrion-ai-history-list">
                            @forelse ($this->conversations() as $conversation)
                                <div wire:key="ai-history-{{ $conversation->id }}" class="patrion-ai-history-item {{ $conversationId === $conversation->id ? 'is-active' : '' }}">
                                    <button type="button" wire:click="selectConversation({{ $conversation->id }})" class="patrion-ai-history-select" title="{{ $conversation->title }}">{{ $conversation->title }}</button>
                                    <button type="button" wire:click="deleteConversation({{ $conversation->id }})" wire:confirm="Eliminare questa conversazione?" class="patrion-ai-history-delete" aria-label="Elimina conversazione">
                                        <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                                    </button>
                                </div>
                            @empty
                                <p class="py-8 text-center text-xs text-gray-500">Nessuna conversazione salvata.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <div class="patrion-ai-messages" x-ref="messages">
                    @if ($this->conversationMessages()->isEmpty())
                        <div class="patrion-ai-empty" x-show="! working">
                            <div>
                                <div class="patrion-ai-empty-mark">
                                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5" />
                                </div>
                                <h3>Come posso aiutarti?</h3>
                                <p>Interrogo appuntamenti, clienti, attività, pratiche e obiettivi usando i dati del gestionale.</p>
                                <div class="patrion-ai-suggestions">
                                    @foreach ([
                                        'Con chi ho appuntamento oggi?',
                                        'Chi è il mio miglior cliente?',
                                        'A che punto sono gli obiettivi attivi?',
                                    ] as $suggestion)
                                        <button type="button" x-on:click="submit(@js($suggestion))" class="patrion-ai-suggestion">
                                            <span>{{ $suggestion }}</span>
                                            <x-filament::icon icon="heroicon-o-arrow-up-right" />
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        @foreach ($this->conversationMessages() as $chatMessage)
                            <div wire:key="ai-message-{{ $chatMessage->id }}" class="patrion-ai-message is-{{ $chatMessage->role }}">
                                @if ($chatMessage->role === 'assistant')
                                    <div class="patrion-ai-assistant-mark"><x-filament::icon icon="heroicon-o-sparkles" class="h-3 w-3" /></div>
                                @endif
                                <div class="patrion-ai-bubble">
                                    @if ($chatMessage->role === 'assistant')
                                        {!! $chatMessage->renderedContent() !!}
                                    @else
                                        <span class="patrion-ai-user-text">{{ $chatMessage->content }}</span>
                                    @endif
                                    <div class="patrion-ai-time">{{ $chatMessage->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div x-cloak x-show="working" class="patrion-ai-message is-user">
                        <div class="patrion-ai-bubble"><span class="patrion-ai-user-text" x-text="optimistic"></span><div class="patrion-ai-time">adesso</div></div>
                    </div>
                    <template x-for="(queuedMessage, index) in queued" :key="`queued-${index}`">
                        <div class="patrion-ai-message is-user">
                            <div class="patrion-ai-bubble"><span class="patrion-ai-user-text" x-text="queuedMessage"></span><div class="patrion-ai-time">in coda</div></div>
                        </div>
                    </template>
                    <div x-cloak x-show="working" class="patrion-ai-message is-assistant">
                        <div class="patrion-ai-assistant-mark"><x-filament::icon icon="heroicon-o-sparkles" class="h-3 w-3" /></div>
                        <div class="patrion-ai-bubble patrion-ai-streaming-preview" aria-label="Risposta in preparazione">
                            <span class="patrion-ai-dot"></span>
                            <span class="patrion-ai-dot"></span>
                            <span class="patrion-ai-dot"></span>
                        </div>
                    </div>
                    <div x-cloak x-show="working" class="patrion-ai-thinking">
                        <span wire:stream="ai-status">Analizzo la richiesta…</span>
                        <span class="patrion-ai-thinking-dots"><span class="patrion-ai-dot"></span><span class="patrion-ai-dot"></span><span class="patrion-ai-dot"></span></span>
                    </div>
                </div>

                <form x-on:submit.prevent="submit()" class="patrion-ai-composer">
                    <div class="patrion-ai-compose-shell">
                        <textarea
                            x-ref="input"
                            x-model="draft"
                            x-on:input="resize()"
                            x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); submit() }"
                            class="patrion-ai-textarea"
                            rows="1"
                            maxlength="4000"
                            placeholder="Scrivi una domanda…"
                            aria-label="Messaggio per l’assistente"
                        ></textarea>
                        <button type="submit" class="patrion-ai-send" x-bind:disabled="! draft.trim()" aria-label="Invia">
                            <x-filament::icon icon="heroicon-o-arrow-up" class="h-4 w-4" />
                        </button>
                    </div>
                    @error('message') <p class="patrion-ai-error">{{ $message }}</p> @enderror
                    <p class="patrion-ai-help"><span class="patrion-ai-help-dot"></span>Dati CRM in sola lettura · Invio per spedire, Maiusc+Invio per andare a capo</p>
                </form>
            </div>
        </section>
        <button
            type="button"
            x-cloak
            x-show="! open"
            x-on:click="open = true; $nextTick(() => { scroll(); $refs.input?.focus() })"
            x-transition.opacity.scale.90.duration.180ms
            class="patrion-ai-launcher"
            aria-label="Apri Assistente AI"
            title="Assistente AI"
        >
            <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" />
            <span class="patrion-ai-launcher-status" aria-hidden="true"></span>
        </button>
</div>
