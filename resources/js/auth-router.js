import Dashboard from './vue/Dashboard'
import PersonalSales from './vue/PersonalSales'
import DirectChildSales from './vue/DirectChildSales'
import BDSales from './vue/BDSales'
import AccountSettings from './vue/AccountSettings'
import Payouts from './vue/Payouts'
import PayoutDetails from './vue/PayoutDetails'

export default {
    mode: 'history',
    base: process.env.BASE_URL,
    routes : [
        {
            path: '/',
            component: Dashboard,
            name: "Dashboard"
        },
        {
            path: '/personal-sales',
            component: PersonalSales,
            name: "Personal Sales"
        },
        {
            path: '/direct-child-sales',
            component: DirectChildSales,
            name: "Direct Sales"
        },
        {
            path: '/tm-sales',
            component: BDSales,
            name: "TM Sales"
        },
        {
            path: '/account-settings',
            component: AccountSettings,
            name: "Account Settings"
        },
        {
            path: '/payouts',
            component: Payouts,
            name: "Payouts"
        },
        {
            path: '/payout/:id',
            component: PayoutDetails,
            name: "PayoutDetails"
        },
        {
            path: '/:lang',
            component: {
                render (c) { return c('router-view') }
            },
            children: [
                {
                    path: '/',
                    component: Dashboard,
                    name: 'dashboardLocale'
                },
                {
                    path: 'personal-sales',
                    component: PersonalSales,
                },
                {
                    path: 'direct-child-sales',
                    component: DirectChildSales,
                },
                {
                    path: '/tm-sales',
                    component: BDSales,
                },
                {
                    path: 'account-settings',
                    component: AccountSettings,
                },
                {
                    path: '/payouts',
                    component: Payouts,
                    name: "Payouts"
                },
                {
                    path: '/payout/:id',
                    component: PayoutDetails,
                    name: "PayoutDetails"
                },
            ]
        }
    ]
};
