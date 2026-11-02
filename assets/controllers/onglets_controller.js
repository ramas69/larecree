import { Controller } from '@hotwired/stimulus';

/*
 * Les étapes d'une formation en onglets : un clic (ou les flèches ← →) montre une étape à la fois.
 */
export default class extends Controller {
    static targets = ['onglet', 'panneau'];

    montrer(event) {
        this.choisir(this.ongletTargets.indexOf(event.currentTarget));
    }

    clavier(event) {
        const pas = { ArrowRight: 1, ArrowLeft: -1 }[event.key];
        if (!pas) return;
        event.preventDefault();
        const n = this.ongletTargets.length;
        const i = (this.ongletTargets.indexOf(event.currentTarget) + pas + n) % n;
        this.choisir(i);
        this.ongletTargets[i].focus();
    }

    choisir(i) {
        this.ongletTargets.forEach((onglet, k) => {
            onglet.setAttribute('aria-selected', String(k === i));
            onglet.tabIndex = k === i ? 0 : -1;
        });
        this.panneauTargets.forEach((panneau, k) => { panneau.hidden = k !== i; });
    }
}
