import './bootstrap';
import './appraisal-kra-workflow';
import './final-admin-hardening';
import './flash-message-dedupe';
import './assistive-tools';
import './career-ai-form-fix';
import './form-help';
document.addEventListener('DOMContentLoaded',()=>{const b=document.body,t=document.querySelector('[data-sidebar-toggle]'),c=document.querySelector('[data-sidebar-close]'),o=document.querySelector('[data-sidebar-overlay]');const close=()=>{b.classList.remove('sidebar-open');if(t)t.setAttribute('aria-expanded','false')};if(t)t.addEventListener('click',()=>{const open=!b.classList.contains('sidebar-open');b.classList.toggle('sidebar-open',open);t.setAttribute('aria-expanded',open?'true':'false')});if(c)c.addEventListener('click',close);if(o)o.addEventListener('click',close);document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});document.querySelectorAll('.ps-link').forEach(a=>a.addEventListener('click',()=>{if(innerWidth<=991)close()}));});

// EH360 PARTICIPANT TABS
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-eh-tabs]').forEach(tabs=>{const buttons=tabs.querySelectorAll('[data-eh-tab]');const panes=tabs.querySelectorAll('[data-eh-pane]');buttons.forEach(button=>{button.addEventListener('click',()=>{const target=button.dataset.ehTab;buttons.forEach(item=>{item.classList.remove('active');item.setAttribute('aria-selected','false')});panes.forEach(pane=>pane.classList.remove('active'));button.classList.add('active');button.setAttribute('aria-selected','true');const pane=tabs.querySelector(`[data-eh-pane="${target}"]`);if(pane)pane.classList.add('active')})});/* Open the tab named in the URL hash, e.g. #cover after a redirect. */const openFromHash=()=>{const wanted=decodeURIComponent((window.location.hash||'').slice(1));if(wanted){const match=[...buttons].find(b=>b.dataset.ehTab===wanted);if(match)match.click()}};openFromHash();window.addEventListener('hashchange',openFromHash)})});


import './admin-modal-crud';
import './notification-modal';

import './admin-universal-responsive';

import './admin-standardisation';

import './admin-canonical-layout';

import './admin-appraisal-kra-tabs';

import './admin-profile-dropdown';
import './phase25-survey-builder';
import './cms-builder';
import './partner-menu';

// Phone layout: filter toggles, table cards, header "More" menu, public menu button. Keep last.
import './mobile-compact';
