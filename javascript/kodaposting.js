/*
    @licstart The following is the entire license notice for the JavaScript code in this page.
    
    This is the code powering <http://freepo.st>.
    Copyright © 2014-2016 zPlus
    Copyright © 2016 Adonay "adfeno" Felipe Nogueira <adfeno@openmailbox.org> <https://libreplanet.org/wiki/User:Adfeno>
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    As additional permission under GNU GPL version 3 section 7, you may
    distribute non-source (e.g., minimized or compacted) forms of that code
    without the copy of the GNU GPL normally required by section 4, provided
    you include this license notice and a URL through which recipients can
    access the Corresponding Source.
    
    @licend The above is the entire license notice for the JavaScript code in this page.
*/

/**
 * Store which keys have been pressed.
 * When a key has been pressed, pressed_key[e.keyCode] will be set
 * to TRUE. When a key is released, pressed_key[e.keyCode] will be
 * set to FALSE.
 */
var pressed_key = [];

/**
 * Change arrows class when voting.
 */
function vote (action, dom_element)
{
    var arrow_up     = dom_element.children[0];
    var vote_counter = dom_element.children[1];
    var arrow_down   = dom_element.children[2];
    
    // Voted/Upvoted
    var current_status = 0;
    
    if (arrow_up.classList.contains('upvoted'))
        current_status = 1;
    
    if (arrow_down.classList.contains('downvoted'))
        current_status = -1;
    
    // Current vote
    var current_vote = Number (vote_counter.textContent);
    
    // Remove class from arrows
    arrow_up.classList.remove ('upvoted');
    arrow_down.classList.remove ('downvoted');
    
    // Toggle upvote class for arrow
    if ("up" == action)
        switch (current_status)
        {
            case -1:
                vote_counter.textContent = current_vote + 2;
                arrow_up.classList.add ('upvoted');
                break;
            case 0:
                vote_counter.textContent = current_vote + 1;
                arrow_up.classList.add ('upvoted');
                break;
            case 1:
                vote_counter.textContent = current_vote - 1;
                break;
        }
    
    // Toggle downvote class for arrow
    if ("down" == action)
        switch (current_status)
        {
            case -1:
                vote_counter.textContent = current_vote + 1;
                break;
            case 0:
                vote_counter.textContent = current_vote - 1;
                arrow_down.classList.add ('downvoted');
                break;
            case 1:
                vote_counter.textContent = current_vote - 2;
                arrow_down.classList.add ('downvoted');
                break;
        }
}

// Wait DOM to be ready...
document.addEventListener ('DOMContentLoaded', function() {
    
    /**
     * A "vote section" is a <span/> containing
     *   - up arrow
     *   - votes sum
     *   - down arrow
     * 
     * However, if the user is not logged in, there's only a text
     * with the sum of votes, eg. "2 votes" (no <tag> children).
     */
    var vote_sections = document.querySelectorAll ('.vote');
    
    // Bind vote() event to up/down vote arrows
    for (var i = 0; i < vote_sections.length; i++)
        // See comment above on the "vote_sections" declaration.
        if (vote_sections[i].children.length > 0)
        {
            vote_sections[i].children[0].addEventListener ('click', function () { vote ('up',   this.parentNode) });
            vote_sections[i].children[2].addEventListener ('click', function () { vote ('down', this.parentNode) });
        }
    
    // Bind onkeydown()/onkeyup() event to keys
    document.onkeydown = document.onkeyup = function(e) {
        // Set the current key code as TRUE/FALSE
        pressed_key[e.keyCode] = e.type == 'keydown';
        
        // If Ctrl+Enter have been pressed
        // Key codes: Ctrl=17, Enter=13
        if (pressed_key[17] && pressed_key[13])
        {
            // Select all forms in the current page with class "shortcut-submit"
            var forms = document.querySelectorAll ("form.shortcut-submit");
            
            for (var i = 0; i < forms.length; i++)
                forms[i].submit ();
        }
    }
    
});
