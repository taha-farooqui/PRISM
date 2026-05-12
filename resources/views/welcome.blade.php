<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Prism AI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-white text-[#140d1b]" style="font-family: 'Space Grotesk', sans-serif;">
    <div class="relative flex min-h-screen w-full flex-col">
      <!-- Header -->
      <header class="fixed top-4 left-0 right-0 z-50 header-blur">
        <div class="container mx-auto px-6 sm:px-6 lg:px-8">
          <div class="flex h-16 items-center justify-center">
            <nav class="flex items-center gap-12 text-lg font-medium border border-zinc-300 px-6 py-2 rounded-full bg-white/80 backdrop-blur-md relative">
              <div class="flex items-center gap-2">
                <img src="{{ asset('assets/images/logo.svg') }}" alt="Prism Logo" class="w-12 h-16">
                <span>PRISM</span>
              </div>

              <a href="#problem" class="hover:text-primary transition-colors">Problem</a>
              <a href="#solutions" class="hover:text-primary transition-colors">Use Cases</a>
              <a href="#how-it-works" class="hover:text-primary transition-colors">How it Works</a>
              <div class="relative group">
                <a href="#features" class="hover:text-primary transition-colors flex items-center">
                  Features
                  <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </a>
                <div class="absolute left-0 mt-8 w-64 bg-white/90 backdrop-blur-md border border-zinc-300 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-50">
                  <div class="p-4">
                    <ul class="space-y-2">
                      <li><a href="#ai-analytics" class="hover:text-primary transition-colors">Customizable with Resources</a></li>
                      <li><a href="#automation" class="hover:text-primary transition-colors">Lecture video generation</a></li>
                      <li><a href="#integration" class="hover:text-primary transition-colors">Quizzes from Past papers</a></li>
                      <li><a href="#customization" class="hover:text-primary transition-colors">Course Generation</a></li>
                    </ul>
                  </div>
                </div>
              </div>
              <a href="#accuracy" class="hover:text-primary transition-colors">Accuracy</a>
              <a href="#pricing" class="hover:text-primary transition-colors">Pricing</a>
              <a href="{{ route('login') }}" class="ml-4 inline-flex items-center justify-center h-12 px-6 bg-black text-white border border-zinc-300 rounded-full hover:bg-zinc-800 transition-colors">Try Prism</a>
            </nav>
          </div>
        </div>
      </header>

      <main class="flex-grow pt-24">
        <!-- Hero -->
        <section class="py-16 md:py-20 hero-section section-spacing">
          <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-6xl md:text-8xl font-bold tracking-tighter max-w-5xl mx-auto">
              Craft Engaging Lectures in Seconds with <span class="gradient-text">Prism AI</span>
            </h1>
            <p class="mt-6 text-lg md:text-xl text-black/70  max-w-3xl mx-auto">
              Our innovative platform leverages artificial intelligence to instantly generate comprehensive lectures, saving you time and enhancing your teaching.
            </p>
            <div class="mt-10 flex justify-center gap-3">
              <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full h-12 px-8 bg-black text-white text-base font-bold hover:bg-black/90 transition-colors">
                <span>Try Prism</span>
              </a>
              <button class="inline-flex items-center justify-center rounded-full h-12 px-8 border border-black text-black text-base font-bold hover:bg-black hover:text-white transition-colors">
                <span>See a Demo</span>
              </button>
            </div>
          </div>
        </section>

        <!-- Problem Section -->
        <section id="problem" class="py-16 md:py-20 section-spacing bg-gradient-to-br from-purple-50/50 to-pink-50/30  relative">
          <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
              <!-- Left Content -->
              <div class="space-y-8">
                <div class="inline-flex items-center px-4 py-2 bg-purple-100  rounded-full">
                  <span class="text-purple-700  font-medium">The Problem</span>
                </div>

                <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                  Content is <span class="gradient-text">Scattered</span> Across Platforms
                </h2>

                <p class="text-lg md:text-xl text-zinc-600  leading-relaxed max-w-lg">
                  Juggling between multiple platforms just to study wastes valuable time and breaks your learning flow.
                </p>
              </div>

              <!-- Right Visual -->
              <div class="relative">
                <div class="relative aspect-square max-w-md mx-auto">
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div class="relative w-full h-full">
                      <!-- Platform icons scattered around -->
                      <div class="absolute top-4 left-4 w-16 h-16 bg-white  rounded-xl shadow-lg flex flex-col items-center justify-center transform rotate-6 hover:rotate-0 transition-transform duration-300">
                        <svg class="w-6 h-6 text-red-500 mb-1" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                        </svg>
                        <span class="text-xs font-medium text-zinc-700 ">YouTube</span>
                      </div>

                      <div class="absolute top-8 right-6 w-16 h-16 bg-white  rounded-xl shadow-lg flex flex-col items-center justify-center transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                        <svg class="w-6 h-6 text-blue-500 mb-1" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        <span class="text-xs font-medium text-zinc-700 ">Slides</span>
                      </div>

                      <div class="absolute bottom-20 left-6 w-16 h-16 bg-white  rounded-xl shadow-lg flex flex-col items-center justify-center transform -rotate-6 hover:rotate-0 transition-transform duration-300">
                        <svg class="w-6 h-6 text-green-500 mb-1" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/>
                        </svg>
                        <span class="text-xs font-medium text-zinc-700 ">Quiz</span>
                      </div>

                      <div class="absolute bottom-12 right-8 w-16 h-16 bg-white  rounded-xl shadow-lg flex flex-col items-center justify-center transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        <svg class="w-6 h-6 text-purple-500 mb-1" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M9,13H15V19H9V13M10,14V18H14V14H10Z"/>
                        </svg>
                        <span class="text-xs font-medium text-zinc-700 ">Papers</span>
                      </div>

                      <!-- Central confusion icon -->
                      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-xl">
                          <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                          </svg>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Curvy Arrow pointing to next section -->
                  <div class="absolute -bottom-16 left-1/2 transform -translate-x-1/2 z-10">
                    <svg class="w-32 h-20 text-purple-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 100 50">
                      <path d="M 10 10 Q 50 5, 90 40" stroke-width="3" stroke-linecap="round"/>
                      <path d="M 85 35 L 90 40 L 85 42" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div class="text-center mt-2">
                      <span class="text-sm font-medium text-purple-600 ">The Solution</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Solutions Section - Stacking Cards -->
        <section id="solutions" class="py-16 md:py-20 section-spacing stacking-section">
          <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center mb-16">
              <h2 class="mt-4 text-4xl md:text-6xl font-bold tracking-tight">
                All in <span class="gradient-text">One </span>Platform!
              </h2>
              <p class="mt-4 text-lg text-zinc-600 ">
                A creative toolkit for course creation, delivery, and exam-focused mastery.
              </p>
            </div>

            <div class="stacking-container space-y-8">
              <!-- PANEL 1 -->
              <div class="stacking-card rounded-3xl border border-zinc-200  bg-zinc-50/90  backdrop-blur-md py-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center pl-12">
                  <div class="lg:col-span-5">
                    <p class="text-xl tracking-wide text-zinc-500 mb-2">Seamlessly</p>
                    <h3 class="text-4xl md:text-5xl font-extrabold leading-tight flex gap-4 items-center">Upload <span class="gradient-text">Resources</span></h3>
                    <p class="mt-5 text-zinc-700 ">
                      Import any learning material - PDFs, videos, documents, or syllabi. Our AI instantly organizes and structures your content into a comprehensive, ready-to-teach course with intelligent pacing and clear learning objectives.
                    </p>
                  </div>
                  <div class="lg:col-span-7">
                    <div class="rounded-2xl">
                      <div class="aspect-[16/9] relative overflow-hidden">
                        <div class="relative aspect-video rounded-3xl overflow-hidden video-style">
                          <video class="h-full w-full object-cover" playsinline autoplay muted loop>
                            <source src="{{ asset('assets/videos/prism.mp4') }}" type="video/mp4" />
                            Your browser does not support the video tag.
                          </video>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- PANEL 2 -->
              <div class="stacking-card rounded-3xl border border-zinc-200  bg-zinc-50/90  backdrop-blur-md py-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center pl-12">
                  <div class="lg:col-span-7">
                    <div class="rounded-2xl">
                      <div class="aspect-[16/9] relative overflow-hidden">
                        <div class="relative aspect-video rounded-3xl overflow-hidden">
                          <img class="h-full w-full object-cover" src="{{ asset('assets/images/video_placeholder_2_replacement.png') }}" alt="Instant Insights Dashboard">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="lg:col-span-5">
                    <p class="text-xl tracking-wide text-zinc-500 mb-2">Get</p>
                    <h3 class="text-4xl md:text-5xl font-extrabold leading-tight flex gap-4 items-center">Instant Insights</h3>
                    <p class="mt-5 text-zinc-700 ">
                      Receive real-time analytics and comprehension insights as you progress. Track learning patterns, identify knowledge gaps, and get personalized recommendations to optimize your study sessions for maximum retention.
                    </p>
                  </div>
                </div>
              </div>

              <!-- PANEL 3 -->
              <div class="stacking-card rounded-3xl border border-zinc-200  bg-zinc-50/90  backdrop-blur-md py-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center pl-12">
                  <div class="lg:col-span-5">
                    <p class="text-xl tracking-wide text-zinc-500 mb-2">Always</p>
                    <h3 class="text-4xl md:text-5xl font-extrabold leading-tight flex gap-4 items-center">Source-Verified Learning</h3>
                    <p class="mt-5 text-zinc-700 ">
                      Every answer comes with transparent source citations and references. Build trust through verifiable information, cross-reference materials instantly, and develop critical thinking with comprehensive source tracking throughout your learning journey.
                    </p>
                  </div>
                  <div class="lg:col-span-7">
                    <div class="rounded-2xl">
                      <div class="aspect-[16/9] relative overflow-hidden">
                        <div class="relative aspect-video rounded-3xl overflow-hidden video-style">
                          <video class="h-full w-full object-cover" playsinline autoplay muted loop>
                            <source src="{{ asset('assets/videos/prism2.mp4') }}" type="video/mp4" />
                            Your browser does not support the video tag.
                          </video>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- PANEL 4 -->
              <div class="stacking-card rounded-3xl border border-zinc-200  bg-zinc-50/90  backdrop-blur-md py-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center pl-12">
                  <div class="lg:col-span-7">
                    <div class="rounded-2xl">
                      <div class="aspect-[16/9] relative overflow-hidden">
                        <div class="relative aspect-video rounded-3xl overflow-hidden">
                          <video class="h-full w-full object-cover" playsinline autoplay muted loop>
                            <source src="{{ asset('assets/videos/prism3.mp4') }}" type="video/mp4" />
                            Your browser does not support the video tag.
                          </video>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="lg:col-span-5">
                    <p class="text-xl tracking-wide text-zinc-500 mb-2">Generate</p>
                    <h3 class="text-4xl md:text-5xl font-extrabold leading-tight flex gap-4 items-center">
                      <span class="gradient-text">Audio </span>Overview
                    </h3>
                    <p class="mt-5 text-zinc-700 ">
                      Transform any content into engaging audio summaries and lectures. Perfect for on-the-go learning, our AI creates natural-sounding narrations that adapt to your pace, making complex topics accessible through immersive audio experiences.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-16 md:py-20 section-spacing">
          <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
              <h2 class="mt-4 text-4xl md:text-6xl font-bold tracking-tight">
                Frequently Asked Questions<span class="gradient-text">.</span>
              </h2>
              <p class="mt-3 text-black/70 ">Answers to the most common questions about Prism AI.</p>
            </div>

            <div class="mx-auto max-w-3xl space-y-3">
              <details class="group border border-zinc-200  p-4 rounded-lg">
                <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">
                  How fast can PRISM generate a lecture?
                  <span class="ml-4 transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="mt-3 text-black/70 ">Most topics generate in seconds. Longer courses or video renders can take a bit more time depending on complexity.</p>
              </details>

              <details class="group border border-zinc-200  p-4 rounded-lg">
                <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">
                  Can I edit the generated content?
                  <span class="ml-4 transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="mt-3 text-black/70 ">Yes. You can tweak outlines, slides, and scripts, then export to your preferred format.</p>
              </details>

              <details class="group border border-zinc-200  p-4 rounded-lg">
                <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">
                  Which export formats do you support?
                  <span class="ml-4 transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="mt-3 text-black/70 ">Presentations (PPTX/PDF), outlines (DOCX/Markdown), and video scripts. More formats are coming soon.</p>
              </details>

              <details class="group border border-zinc-200  p-4 rounded-lg">
                <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">
                  Does PRISM work with my existing syllabus?
                  <span class="ml-4 transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="mt-3 text-black/70 ">Absolutely—upload your syllabus (PDF/DOCX) or paste it in. PRISM builds a full course around it.</p>
              </details>

              <details class="group border border-zinc-200  p-4 rounded-lg">
                <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">
                  Is my data secure?
                  <span class="ml-4 transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="mt-3 text-black/70 ">We use encrypted storage and never share your content. You control what is saved and exported.</p>
              </details>
            </div>
          </div>
        </section>
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-zinc-200">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div>
              <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
                <h2 class="text-xl font-bold">Prism AI</h2>
              </div>
              <p class="mt-4 text-sm text-black/60 ">From syllabus to success—PRISM builds, explains, and assesses your course for you.</p>
            </div>

            <div>
              <h3 class="text-sm font-bold mb-3">Product</h3>
              <ul class="space-y-2 text-sm">
                <li><a class="hover:text-primary transition-colors" href="#solutions">Solutions</a></li>
                <li><a class="hover:text-primary transition-colors" href="#">Features</a></li>
                <li><a class="hover:text-primary transition-colors" href="#pricing">Pricing</a></li>
              </ul>
            </div>

            <div>
              <h3 class="text-sm font-bold mb-3">Resources</h3>
              <ul class="space-y-2 text-sm">
                <li><a class="hover:text-primary transition-colors" href="#faq">FAQs</a></li>
                <li><a class="hover:text-primary transition-colors" href="#resources">Docs</a></li>
                <li><a class="hover:text-primary transition-colors" href="#">Contact</a></li>
              </ul>
            </div>

            <div>
              <h3 class="text-sm font-bold mb-3">Get updates</h3>
              <form class="flex gap-2">
                <input type="email" placeholder="Your email" class="flex-1 border border-zinc-300  bg-transparent px-3 py-2 rounded-lg text-sm" />
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-black text-white text-sm font-bold hover:bg-black/90 transition-colors rounded-lg">Subscribe</button>
              </form>
            </div>
          </div>

          <div class="mt-10 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-black/50 ">
            <p>&copy; {{ date('Y') }} Prism AI. All rights reserved.</p>
            <div class="flex gap-6">
              <a href="#" class="hover:text-primary transition-colors">Privacy</a>
              <a href="#" class="hover:text-primary transition-colors">Terms</a>
              <a href="#" class="hover:text-primary transition-colors">Status</a>
            </div>
          </div>
        </div>
      </footer>
    </div>

    <script>
      function handleVideoScroll() {
        const videoContainer = document.querySelector('.video-container');
        if (!videoContainer) return;

        const videoRect = videoContainer.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const videoTop = videoRect.top;
        const videoBottom = videoRect.bottom;
        const videoHeight = videoRect.height;

        if (videoBottom > 0 && videoTop < windowHeight) {
          let progress = 0;

          if (videoTop <= 0) {
            progress = Math.min(1, Math.abs(videoTop) / (videoHeight - windowHeight));
          } else {
            progress = (windowHeight - videoTop) / windowHeight;
            progress = Math.max(0, Math.min(0.5, progress - 0.2));
          }

          const rotateX = 12 * (1 - progress * 2);
          const rotateY = -0 * (1 - progress * 2);
          const translateZ = 50 * progress * 2;
          const scale = 1 + (progress * 0.1);

          videoContainer.style.transform = `perspective(1000px) rotateX(${Math.max(0, rotateX)}deg) rotateY(${Math.max(-5, rotateY)}deg) translateZ(${translateZ}px) scale(${scale})`;

          if (progress > 0.3) {
            videoContainer.style.boxShadow = `
              0 ${25 * progress}px ${50 * progress}px -12px rgba(0, 0, 0, ${0.25 * progress}),
              0 0 ${100 * progress}px rgba(102, 126, 234, ${0.3 * progress})
            `;
          } else {
            videoContainer.style.boxShadow = 'none';
          }
        }
      }

      function handleStackingCards() {
        const stackingContainer = document.querySelector('.stacking-container');
        const stackingCards = document.querySelectorAll('.stacking-card');

        if (!stackingContainer || !stackingCards.length) return;

        const stickyPositions = [120, 130, 140, 150];
        let stackedCount = 0;

        stackingCards.forEach((card, index) => {
          const cardRect = card.getBoundingClientRect();
          const cardTop = cardRect.top;
          const currentStickyPos = stickyPositions[index];

          if (cardTop <= currentStickyPos + 10) {
            stackedCount++;

            if (!stackingContainer.classList.contains('fully-stacked')) {
              const stackEffect = Math.min((currentStickyPos - cardTop) / 100, 1);
              const scale = 1 - (stackEffect * 0.01);

              card.style.transform = `scale(${Math.max(0.99, scale)})`;
              card.style.boxShadow = `0 ${5 + stackEffect * 10}px ${15 + stackEffect * 10}px -5px rgba(0, 0, 0, ${0.1 + stackEffect * 0.1})`;
            }
          } else {
            if (!stackingContainer.classList.contains('fully-stacked')) {
              card.style.transform = 'scale(1)';
              card.style.boxShadow = 'none';
            }
          }
        });

        if (stackedCount === stackingCards.length && !stackingContainer.classList.contains('fully-stacked')) {
          stackingContainer.classList.add('fully-stacked');

          stackingCards.forEach((card) => {
            card.style.transform = 'scale(1)';
            card.style.boxShadow = '0 10px 25px -12px rgba(0, 0, 0, 0.15)';
          });
        }

        if (stackedCount < stackingCards.length && stackingContainer.classList.contains('fully-stacked')) {
          stackingContainer.classList.remove('fully-stacked');
        }
      }

      let ticking = false;
      function requestTick() {
        if (!ticking) {
          requestAnimationFrame(() => {
            handleVideoScroll();
            handleStackingCards();
          });
          ticking = true;
          setTimeout(() => { ticking = false; }, 16);
        }
      }

      document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('a[href^="#"]');
        navLinks.forEach(link => {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
              const headerHeight = 100;
              const targetPosition = targetElement.offsetTop - headerHeight;

              window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
              });
            }
          });
        });
      });

      window.addEventListener('scroll', requestTick);
      handleVideoScroll();
      handleStackingCards();
    </script>
  </body>
</html>
