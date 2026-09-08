   <header class="navbar navbar-expand-md d-print-none">
       <div class="container-xl">
           <!-- BEGIN NAVBAR TOGGLER -->
           <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
               aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
               <span class="navbar-toggler-icon"></span>
           </button>
           <!-- END NAVBAR TOGGLER -->
           <!-- BEGIN NAVBAR LOGO -->
           <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
               <img src="{{ asset('img/brand-logo.png') }}" width="45" height="45" alt="">
           </div>
           <!-- END NAVBAR LOGO -->
           <div class="navbar-nav flex-row order-md-last">
               <div class="nav-item dropdown">
                   <a href="#" class="nav-link d-flex lh-1 p-0 px-2 waves-effect" data-bs-toggle="dropdown"
                       aria-label="Open user menu">
                       <span class="avatar avatar-1 text-primary bg-primary-subtle"> {{ user_initials() }} </span>
                       <div class="d-none d-xl-block ps-2">
                           <div>{{ current_user()->name }}</div>
                           <div class="mt-1 small text-secondary">{{ current_user()->getRoleNames()->join(', ') }}</div>
                       </div>
                   </a>
                   <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                       {{-- <a href="#" class="dropdown-item d-block d-md-none">Exit Company</a> --}}
                       <a href="{{ route('profiles.profile') }}" class="dropdown-item d-flex align-items-center">
                           <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user"
                               width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                               fill="none" stroke-linecap="round" stroke-linejoin="round">
                               <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                               <circle cx="12" cy="7" r="4" />
                               <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                           </svg>
                           <span class="ms-2">Profile</span>
                       </a>

                       {{-- <a href="{{ route('lock.screen') }}" class="dropdown-item d-flex align-items-center"
                           onclick="lockScreen()">
                           <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock"
                               width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                               fill="none" stroke-linecap="round" stroke-linejoin="round">
                               <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                               <rect x="5" y="11" width="14" height="10" rx="2" />
                               <circle cx="12" cy="16" r="1" />
                               <path d="M8 11v-4a4 4 0 0 1 8 0v4" />
                           </svg>
                           <span class="ms-2">Screen Lock</span>
                       </a> --}}

                       <div class="dropdown-divider"></div>
                       {{-- 
                       <a href="{{ route("logout") }}" class="dropdown-item" id="logout_btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                        <form method="POST" id="logout-form" class="d-none" action="{{ route("logout") }}">
                            @csrf
                        </form> --}}
                       <a href="#"
                           onclick="
                            event.preventDefault();
                            localStorage.clear();
                            sessionStorage.clear();
                            localStorage.setItem('userLoggedOut', 'true'); // 🔹 notify other tabs
                            document.getElementById('logout_form').submit();"
                           class="btn btn-outline-danger w-100">
                           <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-logout"
                               width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                               fill="none" stroke-linecap="round" stroke-linejoin="round">
                               <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                               <path
                                   d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                               <path d="M9 12h12l-3 -3" />
                               <path d="M18 15l3 -3" />
                           </svg>
                           Logout
                       </a>

                   </div>
               </div>
               {{-- 🔴 Hidden Logout Form --}}
               <form id="logout_form" action="{{ route('logout') }}" method="POST" class="d-none">
                   @csrf
               </form>
           </div>
           <div class="collapse navbar-collapse" id="navbar-menu">
               <!-- BEGIN NAVBAR MENU -->
               {{-- <ul class="navbar-nav">
                   <li class="nav-item">
                       <a class="nav-link" href="./">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/home -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                   <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                   <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                               </svg></span>
                           <span class="nav-link-title"> Home </span>
                       </a>
                   </li>
                   <li class="nav-item dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/package -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                                   <path d="M12 12l8 -4.5" />
                                   <path d="M12 12l0 9" />
                                   <path d="M12 12l-8 -4.5" />
                                   <path d="M16 5.25l-8 4.5" />
                               </svg></span>
                           <span class="nav-link-title"> Interface </span>
                       </a>
                       <div class="dropdown-menu">
                           <div class="dropdown-menu-columns">
                               <div class="dropdown-menu-column">
                                   <a class="dropdown-item" href="./accordion.html">
                                       Accordion
                                       <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                   </a>
                                   <a class="dropdown-item" href="./alerts.html"> Alerts </a>
                                   <div class="dropend">
                                       <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication"
                                           data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                           aria-expanded="false">
                                           Authentication
                                       </a>
                                       <div class="dropdown-menu">
                                           <a href="./sign-in.html" class="dropdown-item"> Sign in </a>
                                           <a href="./sign-in-link.html" class="dropdown-item"> Sign in link </a>
                                           <a href="./sign-in-illustration.html" class="dropdown-item"> Sign in with
                                               illustration </a>
                                           <a href="./sign-in-cover.html" class="dropdown-item"> Sign in with cover
                                           </a>
                                           <a href="./sign-up.html" class="dropdown-item"> Sign up </a>
                                           <a href="./forgot-password.html" class="dropdown-item"> Forgot password
                                           </a>
                                           <a href="./terms-of-service.html" class="dropdown-item"> Terms of service
                                           </a>
                                           <a href="./auth-lock.html" class="dropdown-item"> Lock screen </a>
                                           <a href="./2-step-verification.html" class="dropdown-item"> 2 step
                                               verification </a>
                                           <a href="./2-step-verification-code.html" class="dropdown-item"> 2 step
                                               verification code </a>
                                       </div>
                                   </div>
                                   <a class="dropdown-item" href="./avatars.html">
                                       Avatars
                                       <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                   </a>
                                   <a class="dropdown-item" href="./badges.html"> Badges </a>
                                   <a class="dropdown-item" href="./blank.html"> Blank page </a>
                                   <a class="dropdown-item" href="./buttons.html"> Buttons </a>
                                   <div class="dropend">
                                       <a class="dropdown-item dropdown-toggle" href="#sidebar-cards"
                                           data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                           aria-expanded="false">
                                           Cards
                                       </a>
                                       <div class="dropdown-menu">
                                           <a href="./cards.html" class="dropdown-item"> Sample cards </a>
                                           <a href="./card-actions.html" class="dropdown-item">
                                               Card actions
                                               <span
                                                   class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                           </a>
                                           <a href="./cards-masonry.html" class="dropdown-item"> Cards Masonry </a>
                                       </div>
                                   </div>
                                   <a class="dropdown-item" href="./carousel.html"> Carousel </a>
                                   <a class="dropdown-item" href="./colors.html"> Colors </a>
                                   <a class="dropdown-item" href="./datagrid.html"> Data grid </a>
                                   <a class="dropdown-item" href="./dropdowns.html"> Dropdowns </a>
                                   <div class="dropend">
                                       <a class="dropdown-item dropdown-toggle" href="#sidebar-error"
                                           data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button"
                                           aria-expanded="false">
                                           Error pages
                                       </a>
                                       <div class="dropdown-menu">
                                           <a href="./error-404.html" class="dropdown-item"> 404 page </a>
                                           <a href="./error-500.html" class="dropdown-item"> 500 page </a>
                                           <a href="./error-maintenance.html" class="dropdown-item"> Maintenance page
                                           </a>
                                       </div>
                                   </div>
                                   <a class="dropdown-item" href="./lists.html"> Lists </a>
                                   <a class="dropdown-item" href="./modals.html"> Modals </a>
                               </div>
                               <div class="dropdown-menu-column">
                                   <a class="dropdown-item" href="./markdown.html"> Markdown </a>
                                   <a class="dropdown-item" href="./navigation.html"> Navigation </a>
                                   <a class="dropdown-item" href="./offcanvas.html"> Offcanvas </a>
                                   <a class="dropdown-item" href="./pagination.html"> Pagination </a>
                                   <a class="dropdown-item" href="./placeholder.html"> Placeholder </a>
                                   <a class="dropdown-item" href="./segmented-control.html">
                                       Segmented control
                                       <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                   </a>
                                   <a class="dropdown-item" href="./scroll-spy.html">
                                       Scroll spy
                                       <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                   </a>
                                   <a class="dropdown-item" href="./social-icons.html"> Social icons </a>
                                   <a class="dropdown-item" href="./stars-rating.html"> Stars rating </a>
                                   <a class="dropdown-item" href="./steps.html"> Steps </a>
                                   <a class="dropdown-item" href="./tables.html"> Tables </a>
                                   <a class="dropdown-item" href="./tabs.html"> Tabs </a>
                                   <a class="dropdown-item" href="./tags.html"> Tags </a>
                                   <a class="dropdown-item" href="./toasts.html"> Toasts </a>
                                   <a class="dropdown-item" href="./typography.html"> Typography </a>
                               </div>
                           </div>
                       </div>
                   </li>
                   <li class="nav-item dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-form" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/checkbox -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path d="M9 11l3 3l8 -8" />
                                   <path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" />
                               </svg></span>
                           <span class="nav-link-title"> Forms </span>
                       </a>
                       <div class="dropdown-menu">
                           <a class="dropdown-item" href="./form-elements.html"> Form elements </a>
                           <a class="dropdown-item" href="./form-layout.html">
                               Form layouts
                               <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                           </a>
                       </div>
                   </li>
                   <li class="nav-item dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/star -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path
                                       d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                               </svg></span>
                           <span class="nav-link-title"> Extra </span>
                       </a>
                       <div class="dropdown-menu">
                           <div class="dropdown-menu-columns">
                               <div class="dropdown-menu-column">
                                   <a class="dropdown-item" href="./activity.html"> Activity </a>
                                   <a class="dropdown-item" href="./chat.html"> Chat </a>
                                   <a class="dropdown-item" href="./cookie-banner.html"> Cookie banner </a>
                                   <a class="dropdown-item" href="./empty.html"> Empty page </a>
                                   <a class="dropdown-item" href="./faq.html"> FAQ </a>
                                   <a class="dropdown-item" href="./gallery.html"> Gallery </a>
                                   <a class="dropdown-item" href="./invoice.html"> Invoice </a>
                                   <a class="dropdown-item" href="./job-listing.html"> Job listing </a>
                                   <a class="dropdown-item" href="./license.html"> License </a>
                                   <a class="dropdown-item" href="./logs.html"> Logs </a>
                                   <a class="dropdown-item" href="./marketing/index.html"> Marketing </a>
                                   <a class="dropdown-item" href="./music.html"> Music </a>
                                   <a class="dropdown-item" href="./page-loader.html"> Page loader </a>
                               </div>
                               <div class="dropdown-menu-column">
                                   <a class="dropdown-item" href="./photogrid.html"> Photogrid </a>
                                   <a class="dropdown-item" href="./pricing.html"> Pricing cards </a>
                                   <a class="dropdown-item" href="./pricing-table.html"> Pricing table </a>
                                   <a class="dropdown-item" href="./search-results.html"> Search results </a>
                                   <a class="dropdown-item" href="./settings.html"> Settings </a>
                                   <a class="dropdown-item" href="./signatures.html">
                                       Signatures
                                       <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                   </a>
                                   <a class="dropdown-item" href="./tasks.html"> Tasks </a>
                                   <a class="dropdown-item" href="./text-features.html">
                                       Text features
                                       <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                   </a>
                                   <a class="dropdown-item" href="./trial-ended.html"> Trial ended </a>
                                   <a class="dropdown-item" href="./uptime.html"> Uptime monitor </a>
                                   <a class="dropdown-item" href="./users.html"> Users </a>
                                   <a class="dropdown-item" href="./widgets.html"> Widgets </a>
                                   <a class="dropdown-item" href="./wizard.html"> Wizard </a>
                               </div>
                           </div>
                       </div>
                   </li>
                   <li class="nav-item active dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-layout" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/layout-2 -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path
                                       d="M4 4m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                   <path
                                       d="M4 13m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                   <path
                                       d="M14 4m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                   <path
                                       d="M14 15m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                               </svg></span>
                           <span class="nav-link-title"> Layout </span>
                       </a>
                       <div class="dropdown-menu">
                           <div class="dropdown-menu-columns">
                               <div class="dropdown-menu-column">
                                   <a class="dropdown-item" href="./layout-boxed.html"> Boxed </a>
                                   <a class="dropdown-item" href="./layout-combo.html"> Combined </a>
                                   <a class="dropdown-item active" href="./layout-condensed.html"> Condensed </a>
                                   <a class="dropdown-item" href="./layout-fluid.html"> Fluid </a>
                                   <a class="dropdown-item" href="./layout-fluid-vertical.html"> Fluid vertical </a>
                                   <a class="dropdown-item" href="./layout-horizontal.html"> Horizontal </a>
                                   <a class="dropdown-item" href="./layout-navbar-dark.html"> Navbar dark </a>
                               </div>
                               <div class="dropdown-menu-column">
                                   <a class="dropdown-item" href="./layout-navbar-overlap.html"> Navbar overlap </a>
                                   <a class="dropdown-item" href="./layout-navbar-sticky.html"> Navbar sticky </a>
                                   <a class="dropdown-item" href="./layout-vertical-right.html"> Right vertical </a>
                                   <a class="dropdown-item" href="./layout-rtl.html"> RTL mode </a>
                                   <a class="dropdown-item" href="./layout-vertical.html"> Vertical </a>
                                   <a class="dropdown-item" href="./layout-vertical-transparent.html"> Vertical
                                       transparent </a>
                               </div>
                           </div>
                       </div>
                   </li>
                   <li class="nav-item dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-plugins" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/puzzle -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path
                                       d="M4 7h3a1 1 0 0 0 1 -1v-1a2 2 0 0 1 4 0v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v3a1 1 0 0 0 1 1h1a2 2 0 0 1 0 4h-1a1 1 0 0 0 -1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-1a2 2 0 0 0 -4 0v1a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h1a2 2 0 0 0 0 -4h-1a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1" />
                               </svg></span>
                           <span class="nav-link-title"> Plugins </span>
                       </a>
                       <div class="dropdown-menu">
                           <a class="dropdown-item" href="./charts.html"> Charts </a>
                           <a class="dropdown-item" href="./colorpicker.html"> Color picker </a>
                           <a class="dropdown-item" href="./datatables.html"> Datatables </a>
                           <a class="dropdown-item" href="./dropzone.html"> Dropzone </a>
                           <a class="dropdown-item" href="./fullcalendar.html"> Fullcalendar </a>
                           <a class="dropdown-item" href="./inline-player.html"> Inline player </a>
                           <a class="dropdown-item" href="./lightbox.html"> Lightbox </a>
                           <a class="dropdown-item" href="./maps.html"> Map </a>
                           <a class="dropdown-item" href="./map-fullsize.html"> Map fullsize </a>
                           <a class="dropdown-item" href="./maps-vector.html"> Map vector </a>
                           <a class="dropdown-item" href="./turbo-loader.html"> Turbo loader </a>
                           <a class="dropdown-item" href="./wysiwyg.html"> WYSIWYG editor </a>
                       </div>
                   </li>
                   <li class="nav-item dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-addons" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/gift -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path
                                       d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" />
                                   <path d="M12 8l0 13" />
                                   <path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" />
                                   <path
                                       d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" />
                               </svg></span>
                           <span class="nav-link-title"> Addons </span>
                       </a>
                       <div class="dropdown-menu">
                           <a class="dropdown-item" href="./icons.html"> Icons </a>
                           <a class="dropdown-item" href="./emails.html"> Emails </a>
                           <a class="dropdown-item" href="./flags.html"> Flags </a>
                           <a class="dropdown-item" href="./illustrations.html"> Illustrations </a>
                           <a class="dropdown-item" href="./payment-providers.html"> Payment providers </a>
                       </div>
                   </li>
                   <li class="nav-item dropdown">
                       <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown"
                           data-bs-auto-close="outside" role="button" aria-expanded="false">
                           <span
                               class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler.io/icons/icon/lifebuoy -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                   <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                   <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                   <path d="M15 15l3.35 3.35" />
                                   <path d="M9 15l-3.35 3.35" />
                                   <path d="M5.65 5.65l3.35 3.35" />
                                   <path d="M18.35 5.65l-3.35 3.35" />
                               </svg></span>
                           <span class="nav-link-title"> Help </span>
                       </a>
                       <div class="dropdown-menu">
                           <a class="dropdown-item" href="https://tabler.io/docs" target="_blank" rel="noopener">
                               Documentation </a>
                           <a class="dropdown-item" href="./changelog.html"> Changelog </a>
                           <a class="dropdown-item" href="https://github.com/tabler/tabler" target="_blank"
                               rel="noopener"> Source code </a>
                           <a class="dropdown-item text-pink" href="https://github.com/sponsors/codecalm"
                               target="_blank" rel="noopener">
                               <!-- Download SVG icon from http://tabler.io/icons/icon/heart -->
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                   stroke-linecap="round" stroke-linejoin="round"
                                   class="icon icon-inline me-1 icon-2">
                                   <path
                                       d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" />
                               </svg>
                               Sponsor project!
                           </a>
                       </div>
                   </li>
               </ul> --}}
               <ul class="navbar-nav">
                    @foreach (config('company_selector_menu.main') as $item)
                        @if (menu_can_access($item))
                            @php
                                $url      = isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
                                $iconName = $item['icon'] ?? '';
                                $iconView = 'icons.' . $iconName;
                                $iconSize = $item['icon_size'] ?? 24;
                                $isFa     = str_starts_with($iconName, 'fa');
                                $isActive = menu_is_active($item);
                            @endphp

                            <li class="nav-item @if (isset($item['children'])) dropdown @endif @if ($isActive) active @endif">
                                <a class="nav-link @if (isset($item['children'])) dropdown-toggle gap-1 align-items-center fs-4 fw-bold @endif @if ($isActive) bg-primary-lt rounded @endif"
                                    @if (isset($item['children'])) data-bs-toggle="dropdown" @endif
                                    href="{{ $url }}" role="button" aria-expanded="false">

                                    @if (!empty($iconName))
                                        <span class="nav-link-icon d-md-none d-lg-inline-block text-primary">
                                            @if($isFa)
                                                <span class="d-inline-flex align-items-center justify-content-center rounded-2"
                                                    style="width:{{ $iconSize + 5 }}px;height:{{ $iconSize + 2 }}px;background:transparent;">
                                                    <i class="{{ $iconName }}" style="font-size:{{ $iconSize - 4 }}px;color:currentColor;"></i>
                                                </span>
                                            @elseif(view()->exists($iconView))
                                                @include($iconView, ['size' => $iconSize])
                                            @endif
                                        </span>
                                    @endif

                                    <span class="nav-link-title">{{ $item['title'] }}</span>
                                </a>

                                @if (isset($item['children']))
                                    <div class="dropdown-menu">
                                        @foreach ($item['children'] as $child)
                                            @include('company-selector.partials.sub-menu-item', [
                                                'item' => $child,
                                            ])
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>


               <!-- END NAVBAR MENU -->
           </div>
       </div>
   </header>
