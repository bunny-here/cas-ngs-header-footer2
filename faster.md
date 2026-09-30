# CAS-NGS Homepage Performance Plan

## Assessment

The reported LCP values are poor: 10.79 s with the corridor and 12.73 s without it. The recorded LCP elements are corridor heading `#cor3d-title-1` and Act 1 paragraph `.hero-sub`. That suggests the corridor is not the sole cause. Several independent code paths delay first paint, hide above-the-fold copy, or do work across the whole page.

The higher reading after removing the corridor does not show that the corridor improved performance: the LCP element changed, and the splash plus hidden Act 1 copy remained. Compare repeated cold and warm traces before attributing the difference to one block.

No browser performance trace or live network waterfall is available in this workspace, so the points below distinguish confirmed implementation costs from hypotheses that should be checked against a fresh trace.

## Confirmed Findings

| Priority | Finding | Evidence and impact |
|---|---|---|
| P0 | Splash is a blocking visual gate. | [`cas-ngs-header-footer.php`](cas-ngs-header-footer.php) inserts the splash on every frontend page and enqueues its CSS/JS. [`cas-splash.css`](assets/css/cas-splash.css) hides every other direct body child while the splash is active. The animation waits for DOM readiness and GSAP, then runs a multi-stage timeline before revealing the page. This can directly delay LCP, especially on a fresh session. |
| P0 | Above-the-fold copy starts hidden. | [`biotech-blocks.css`](assets/css/biotech-blocks.css) sets `.gsap-fade-up` and `.gsap-scale-in` to zero opacity. Act 1 marks its heading, subtitle, CTA, and media this way; the engine reveals them only after its frontend runtime initializes. The corridor CSS also starts station anchors hidden. This makes LCP depend on JavaScript/animation instead of HTML/CSS rendering. |
| P0 | Scroll is intentionally intercepted by the pipeline. | [`biotech-blocks-engine.js`](assets/js/biotech-blocks-engine.js) initializes the interactive pipeline with `engaged = true`, creates a window-level wheel/touch Observer using `preventDefault`, and resets the window scroll position while engaged. Its input handler is gated on `booted`, but the observer and scroll handler are already active. Boot becomes ready when the GLB finishes or a five-second fallback timer fires. The corridor also installs a `preventDefault` Observer while inside its pin zone. Together these explain scroll feeling trapped when either hero is present and make overlapping interactive heroes risky. |
| P1 | Heavy frontend assets are enqueued on every page. | [`cas_bio_enqueue_frontend_assets()`](includes/cas-ngs-biotech-blocks.php) unconditionally enqueues the shared biotech stylesheet/runtime, GSAP, ScrollTrigger, Observer, Three.js, postprocessing scripts, GLTFLoader, the DNA enhancer, and corridor assets. The header/footer bootstrap also enqueues its assets and fonts site-wide. Most of these are not needed on pages without those blocks. |
| P1 | GSAP is enqueued twice at different versions. | The splash registers GSAP 3.12.2 under `cas-ngs-gsap`; the biotech runtime registers GSAP 3.12.5 under `gsap`. They are distinct URLs/handles and may both download and execute. |
| P1 | 3D scenes have substantial transfer and runtime cost. | [`assets/models/dna.glb`](assets/models/dna.glb) is 3,392,288 bytes and about 3.25 MiB after gzip, so gzip does little to shrink it. The corridor and interactive pipeline each create their own WebGL renderer, shadows, and optional EffectComposer pass. If both blocks use the same model on one page, the model can be parsed and uploaded to separate GPU contexts. |
| P1 | Render loops do not fully stop when idle/offscreen. | Corridor and pipeline schedule a new `requestAnimationFrame` every frame; the pipeline skips some drawing when stopped, but its callback continues. The DNA enhancer renders continuously and rotates the model even when the wrapper is offscreen or reduced motion is requested. The pipeline also runs a 130 ms ticker interval; waveform/timer intervals are created for matching blocks without cleanup. |
| P2 | Corridor has deliberately large rendering settings. | [`corridor-hero-engine.js`](assets/js/corridor-hero-engine.js) creates a full-viewport WebGL renderer with antialiasing, a 2048px shadow map, and postprocessing when available. Its block uses a 400vh pinned stage. This may be acceptable as an opt-in hero, but is a poor default cost when loaded or initialized early. |

## Recommended Execution

Implement one phase at a time, profile after each phase, and keep visual comparisons against the current design. Do not remove the block designs wholesale; make their expensive behavior conditional and retain graceful static fallbacks.

### Phase 0: Capture a Baseline

1. Use staging or a private test page. Capture a cold first visit and a repeat visit separately because the splash stores completion in session storage.
2. In Chrome Performance, record navigation through at least the first viewport and one full scroll. Capture LCP subparts, long tasks, scripting time, GPU frames, memory, and loaded JS/model resources.
3. Record which blocks are present and whether the plugin is active. Test a clean theme page, the homepage with only Act 1–4, the pipeline alone, the corridor alone, and the current combined page.
4. Keep the current reported LCP values as the baseline, but use the trace to tell apart server response, resource-load delay, and render delay.

### Phase 1: Remove LCP and Scroll Regressions

1. Make the splash non-blocking: never hide page content while waiting for GSAP or animation completion. Prefer an opt-in splash or a short, non-blocking transition after content is visible. Honor `prefers-reduced-motion` and reduced-data/save-data preferences. Avoid shipping a second GSAP version just for the splash.
2. Make hero titles, paragraphs, and primary CTAs visible in the initial HTML/CSS. Apply entrance motion only as progressive enhancement after runtime readiness; if JavaScript fails or is delayed, content must remain visible.
3. Keep corridor card content visible as a static fallback, then enhance it when the scene is ready. Do not make the LCP heading wait for model download, WebGL setup, or a ScrollTrigger callback.
4. Scope wheel/touch interception strictly to the active pinned scene. Do not start the pipeline Observer before the pipeline is ready and actually in its interaction window. Remove global scroll-to-top enforcement; release native scroll at the first/last station and whenever the scene exits view. Ensure the corridor and pipeline cannot both capture input at once.

### Phase 2: Load Only What the Current Page Uses

1. Split asset registration from asset enqueueing. Register handles centrally, but enqueue the biotech runtime, GSAP plugins, Three.js add-ons, DNA enhancer, and corridor files only when the matching block/shortcode is present.
2. Preserve support for Full Site Editing templates and patterns: a simple `has_block()` check against only `post_content` can miss blocks stored in template parts. Prefer WordPress block asset metadata where appropriate, or a tested block-aware frontend asset strategy that covers template rendering and shortcodes.
3. Keep the HTML-only Act 1–4 blocks independent of Three.js. Load Three.js/GLTFLoader only for a visible 3D pipeline, corridor, or DNA background. Load postprocessing passes only when a scene actually uses them.
4. Consolidate GSAP to one version and one handle. The header's ambient effect should not trigger a separate Three.js download on ordinary pages; consider replacing that small effect with CSS or a lightweight canvas implementation.
5. Keep editor-only scripts and preview dependencies out of visitor-facing pages.

### Phase 3: Make Animation Work Demand-Driven

1. Use `IntersectionObserver` to defer expensive scene creation until a 3D block approaches the viewport. Start rendering only while visible, pause when offscreen or the document is hidden, and resume on re-entry.
2. Stop scheduling animation frames when there is no active animation. Store the frame handle, cancel it on pause/destroy, and disconnect observers/listeners when a block is removed or navigation changes.
3. Pause CSS animation, GSAP timelines, intervals, and text updates for offscreen blocks. Replace frequent DOM text writes (including the pipeline ticker) with updates only when displayed values change.
4. Honor reduced motion consistently across CSS, GSAP, Observer, and WebGL. Reduced motion should avoid initializing continuous 3D animation where a static scene is sufficient.
5. Prevent duplicate heavyweight scenes. If both the pipeline and corridor appear on the same page, either share model data safely or show one scene as a static/lightweight fallback; do not create competing renderers by default.

### Phase 4: Reduce 3D and Asset Cost

1. Optimize the GLB with a repeatable asset pipeline: remove unused meshes/materials/animations, merge or simplify geometry where visually safe, and test mesh/texture compression. Measure decode time and visual quality as well as file size.
2. Lower the mobile pixel-ratio cap and make quality adaptive to device capability. Test whether the 2048px shadow map and EffectComposer pass are visually necessary; provide a lower-cost default and an optional high-quality mode.
3. Reduce per-frame instance updates and particle counts. Update static geometry once, update only visible effects, and use CSS transforms/opacity for DOM overlays rather than layout-affecting properties.
4. Add explicit image dimensions and sensible responsive sizes to any raster media used in homepage sections. Lazy-load below-the-fold images, but do not lazy-load the actual LCP image. Self-host or selectively enqueue fonts if the trace shows font requests delaying rendering.

## Validation Targets

- Above-the-fold heading and paragraph remain visible with JavaScript disabled or delayed.
- Splash does not delay content visibility or LCP.
- A page with no 3D block makes no Three.js, postprocessing, or GLB requests.
- A page with one 3D block loads only its required libraries/model; the renderer pauses offscreen and when the tab is hidden.
- Wheel, touch, keyboard, and scrollbar scrolling work normally outside an active pinned scene; exiting the scene always returns control to native page scroll.
- No persistent RAF/timer work remains for removed, hidden, or inactive blocks.
- Profile cold and warm visits on desktop and mobile. Aim for LCP below 2.5 s on the agreed representative connection/device, no long task over 200 ms during initial render, and smooth scrolling without sustained dropped frames. Keep visual snapshots to verify the earth palette and section appearance did not regress.

## Suggested Order

1. Fix splash/LCP visibility and the pipeline scroll lock first; these are confirmed user-facing problems.
2. Stop global loading of 3D dependencies and load assets only for rendered blocks.
3. Pause offscreen loops and reduce scene/model cost.
4. Re-profile after every phase; only then tune typography/fonts/images or revise individual animation details.

The current evidence is sufficient to identify major hotspots, but a production-ready fix should be guided by a fresh browser trace after each phase. Changing all animation systems at once would make regressions difficult to locate.