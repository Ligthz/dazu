import Login from './vue/Login'

export default {
    mode: 'history',
    base: process.env.BASE_URL,
    routes : [
        {
            path: '/',
            component: Login,
            name: "Partner Login"
        },
        {
            path: '/:lang',
            component: {
                render (c) { return c('router-view') }
            },
            children: [
                {
                    path: '/',
                    component: Login,
                    name: "Partner Login"
                }
            ]

        }
    ]
};
