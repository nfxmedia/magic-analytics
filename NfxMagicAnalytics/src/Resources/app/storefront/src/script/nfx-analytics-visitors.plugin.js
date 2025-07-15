import Storage from 'src/helper/storage/storage.helper';

export default class NfxAnalyticsVisitorsPlugin extends window.PluginBaseClass
{
    static options = {
        action: 'data-nfx-analytics-visitors',
        visitorStorageKey: 'nfx-sw-visit',
        visitorTime: 'data-nfx-analytics-visitor-time',
        referer: 'data-nfx-analytics-referer'
    };

    init() {
        this._request = new XMLHttpRequest();
        this.updateData();
    }

    updateData() {
        const userAgent = navigator.userAgent;
        if (userAgent && /bot|googlebot|robot|baidu|crawl|crawler|bingbot|yahoo|yandexbot|msnbot|slurp|spider|mediapartners/i.test(userAgent)) {
            return;
        }

        // requestUrl -> .../widgets/nfx/analytics/visitors/{controller};{parameter1};{parameter2};{isNew}
        // mit controller (page type), parameter1/2 (verschiedene Daten wie ids, search terms ect., isNew (1: Neukunde, 0 wiederkehrend)
        let requestUrl = this.el.getAttribute(this.options.action);
        if (!requestUrl) return;

        //referer durchreichen in header nfx-referer
        const referer = this.el.getAttribute(this.options.referer);
        const now = new Date();
        const storedDate = Storage.getItem(this.options.visitorStorageKey);
        const visitorTime = parseInt(this.el.getAttribute(this.options.visitorTime) ?? 12, 10);

        try {
            //New Visitor
            if (!storedDate) {
                requestUrl = requestUrl.substring(0, requestUrl.length - 1) + '1';
                Storage.setItem(this.options.visitorStorageKey, now.toISOString());
            } else
            //Returning visitor, check time diff
            if (storedDate && (now - new Date(storedDate))/3600000 > visitorTime) {
                requestUrl = requestUrl.substring(0, requestUrl.length - 1) + '1';
                Storage.setItem(this.options.visitorStorageKey, now.toISOString());
            }

        } catch (e) {
            return;
        }

        this._request.open('GET', requestUrl);
        this._request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        this._request.setRequestHeader('Content-type', 'application/json');
        if (referer) {
            //falls referer vorhanden, im Header mitgeben
            this._request.setRequestHeader('nfx-referer', referer);
        }

        this._request.send();
    }
}
