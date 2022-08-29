# Participant recruitment and appointment website


Conducting psychological research in visual attention frequently involves recruiting research participants and asking them to come to our lab for one or half an hour of experiment sessions. The usual way of recruiting participants, especially 10+ years ago when I was doing my post-doc, is to stick posters everywhere every time you run a new experiment and get participants to call you and make an appointment.

To simplify this process, I wrote a website in PHP to get student participants to register an account on our online system and input their emails there. Every time I run a new experiment, I can send a mass email to potential participants through the system. The system automatically cycles through the participants and allows us to specify how many emails to reach. Then, I only need to recruit participants to my system once every year.

When the potential participants receive the notification, they can see the experiment timeslots and details like how long a specific experiment is and the monetary reward associated with it. If they are interested in participating, they register online.

On the admin page, signed-up participants appear as a list, paged by experiment, and experimental timeslots are shown in a calendar view.

Very soon after I developed this system, it was known by my labmates and friends from my nearby cluster of labs (most of them doing visual perception research). I then called it the [participant website for the vision laboratories at HKU](http://cha.psy.hku.hk/participate/). It remained actively in use until COVID-19, which stopped all face-to-face experiments.
