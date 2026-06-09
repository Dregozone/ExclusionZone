<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class StoryQuestSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::all()->keyBy('city');
        $items = Item::all()->keyBy('key');
        $skills = Skill::all()->keyBy('key');

        $engineering = $skills['engineering'] ?? null;
        $survival = $skills['survival'] ?? null;
        $scavenging = $skills['scavenging'] ?? null;
        $stealth = $skills['stealth'] ?? null;
        $barter = $skills['barter'] ?? null;

        // Quest 1: Static — Kyiv
        $q1 = $this->createQuest(
            name: 'Static',
            description: 'A radio operator in Kyiv has been picking up strange encoded signals on the old emergency bands. He\'s convinced they\'re pre-war military transmissions — and he needs your help to decode them.',
            sequenceOrder: 1,
            rewardSkill: $engineering,
            rewardXp: 300,
            rewardItem: $items['signal_booster'] ?? null,
            rewardItemQty: 1,
        );
        $q1->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Viktor the Operator',
            'action_label' => 'Listen to Viktor\'s findings',
            'interaction_text' => 'Been picking up fragments on the old emergency bands for weeks. Military encoding, pre-war timestamps. I\'m getting somewhere but the hardware is completely shot. Find me a circuit board — anything I can use to rebuild the receiver — and I can clean this signal right up.',
        ]);
        $q1->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Viktor the Operator',
            'action_label' => 'Bring Viktor a circuit board',
            'interaction_text' => 'You beauty. Give me twenty minutes with this. ... There. It\'s coming through clear. Coordinates, encryption keys, and a name: DAWNWATCH. Someone out there knows what started this war — and they\'ve been broadcasting for months. I\'ve rigged you a signal booster. You\'re going to need it.',
            'required_item_id' => $items['circuit_boards']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 2: The Signal — Kyiv
        $q2 = $this->createQuest(
            name: 'The Signal',
            description: 'Viktor has decoded part of the transmission. The source is close — somewhere in the Zone. Use the signal booster to pin the location before the broadcast window closes.',
            sequenceOrder: 2,
            prerequisite: $q1,
            rewardSkill: $engineering,
            rewardXp: 400,
            rewardItem: $items['dawnwatch_document'] ?? null,
            rewardItemQty: 1,
        );
        $q2->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Viktor the Operator',
            'action_label' => 'Review the transmission with Viktor',
            'interaction_text' => 'I ran the decryption overnight. It\'s from a woman called Dr. Elaine Moss — former systems architect for something called the Dawnwatch Protocol. A NATO early-warning AI. She says it went autonomous and triggered the first launch sequence without human authorisation. She\'s alive, she\'s in Pripyat, and she\'s been trying to reach someone for six months.',
        ]);
        $q2->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Viktor the Operator',
            'action_label' => 'Boost the signal to lock the location',
            'interaction_text' => 'Signal locked. She\'s in the old scientific district — east end of the exclusion zone. Transmission repeats every 72 hours. I\'ve printed the decoded coordinates and encryption keys. Don\'t lose them. And watch yourself in the Zone — the radiation dosing near the plant is not a joke.',
            'required_item_id' => $items['signal_booster']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 3: Into the Zone — Kyiv → Pripyat
        $q3 = $this->createQuest(
            name: 'Into the Zone',
            description: 'The signal source is in Pripyat. Viktor has given you a map of the safest approach. Find Dr. Elaine Moss in the old scientific district before the next transmission window closes.',
            sequenceOrder: 3,
            prerequisite: $q2,
            rewardSkill: $survival,
            rewardXp: 500,
        );
        $q3->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Viktor the Operator',
            'action_label' => 'Get Viktor\'s approach map for the Zone',
            'interaction_text' => 'Old scientific block is manageable if you move fast and stick to the eastern approach — less mutant activity, lower rads. The main reactor district is still a no-go. Look for a woman, survivor type. If she\'s been there six months alone, she knows what she\'s doing. Take the eastern road out of Kyiv and don\'t stop.',
        ]);
        $q3->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Pripyat']->id,
            'person_of_interest' => 'Dr. Elaine Moss',
            'action_label' => 'Find Dr. Moss in the Zone',
            'interaction_text' => 'You actually came. I\'ve been broadcasting for six months and you\'re the first person to respond. Sit down — there\'s a lot to tell you, and most of it you\'re not going to want to hear. DAWNWATCH didn\'t malfunction. Someone gave it override permissions it was never supposed to have. The nuclear exchange wasn\'t a miscalculation. Someone made it happen.',
        ]);

        // Quest 4: The Files — Pripyat
        $q4 = $this->createQuest(
            name: 'The Files',
            description: 'Dr. Moss has hard copies of her research in the old institute — but the radiation spiked last week and she needs ionisation data before she can plot a safe route. Help her recover the evidence that could explain who started the war.',
            sequenceOrder: 4,
            prerequisite: $q3,
            rewardSkill: $scavenging,
            rewardXp: 600,
            rewardItem: $items['cold_war_dossier'] ?? null,
            rewardItemQty: 1,
        );
        $q4->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Pripyat']->id,
            'person_of_interest' => 'Dr. Elaine Moss',
            'action_label' => 'Gather radiation samples for Dr. Moss',
            'interaction_text' => 'The documents are in the old institute — sublevel two. But the radiation spiked after last week\'s weather event and I can\'t plot a safe route without current ionisation data. Find me irradiated samples from the hot zone. I can use them to recalibrate my instruments and map a path through.',
            'required_item_id' => $items['irradiated_samples']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);
        $q4->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Pripyat']->id,
            'person_of_interest' => 'Dr. Elaine Moss',
            'action_label' => 'Give Moss the DAWNWATCH documents to verify',
            'interaction_text' => 'These match. The transmission coordinates align with the original programme files I pulled from the institute. DAWNWATCH received its activation override from an external source — identical false-flag sensor data injected simultaneously into its Russian counterpart, a system called ZARYA. Both AIs saw what appeared to be an imminent launch and responded. Whoever fed them that data controlled both sides of the exchange. The trail leads to a courier network operating through Warsaw. Here — take the full dossier.',
            'required_item_id' => $items['dawnwatch_document']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 5: The Courier Network — Pripyat → Warsaw
        $q5 = $this->createQuest(
            name: 'The Courier Network',
            description: 'Dr. Moss has a contact in Warsaw — Dr. Andrzej Kowalski, an academic who monitored both AI programmes before the war. He holds half the evidence. Deliver the dossier and find out what he knows.',
            sequenceOrder: 5,
            prerequisite: $q4,
            rewardSkill: $stealth,
            rewardXp: 700,
        );
        $q5->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Pripyat']->id,
            'person_of_interest' => 'Dr. Elaine Moss',
            'action_label' => 'Receive Kowalski\'s contact details from Moss',
            'interaction_text' => 'Dr. Kowalski in Warsaw. He was part of an independent academic group that tracked both programmes. He doesn\'t know what I know, but he has pre-war intelligence files I couldn\'t get access to. The dossier I\'ve given you contains everything from my side. Get it to him. He\'ll understand the implications immediately.',
        ]);
        $q5->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Warsaw']->id,
            'person_of_interest' => 'Dr. Andrzej Kowalski',
            'action_label' => 'Deliver the dossier to Dr. Kowalski',
            'interaction_text' => 'My God. Elaine is alive. And she found the parallel activation logs — this confirms everything we suspected. The override signal was injected by a third party. Not NATO command, not Russian. Someone else. I have a contact in Gdansk who moved equipment for the original programme contractors back before the war. A smuggler named Halvorsen. He can get you north — and north is where the trail leads.',
            'required_item_id' => $items['cold_war_dossier']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 6: Old Contacts — Warsaw
        $q6 = $this->createQuest(
            name: 'Old Contacts',
            description: 'Kowalski needs time to verify the decryption keys. He\'s also desperate for supplies. Help him out and he\'ll give you everything you need to reach Halvorsen in Gdansk.',
            sequenceOrder: 6,
            prerequisite: $q5,
            rewardSkill: $barter,
            rewardXp: 800,
            rewardItem: $items['courier_token'] ?? null,
            rewardItemQty: 1,
        );
        $q6->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Warsaw']->id,
            'person_of_interest' => 'Dr. Andrzej Kowalski',
            'action_label' => 'Check in with Kowalski on his progress',
            'interaction_text' => 'I\'m cross-referencing the decryption keys with my own records — it will take a day, but I\'ll have everything prepared for Halvorsen\'s introduction. In the meantime, I hate to ask, but I\'m in a difficult position. My food situation is... poor. If you can find me trade ledgers — anything I can exchange for supplies on the black market — I\'ll owe you considerably.',
        ]);
        $q6->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Warsaw']->id,
            'person_of_interest' => 'Dr. Andrzej Kowalski',
            'action_label' => 'Bring trade ledgers to Kowalski',
            'interaction_text' => 'Thank you. Genuinely. Here — Halvorsen\'s berth coordinates and an introduction token. He won\'t speak to strangers, but this will identify you as my contact. Be careful in Gdansk. The old dock districts are contested territory and Halvorsen doesn\'t stay in one place for long.',
            'required_item_id' => $items['trade_ledger']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 7: The Smuggler — Warsaw → Gdansk
        $q7 = $this->createQuest(
            name: 'The Smuggler',
            description: 'Captain Halvorsen is somewhere in the Gdansk docks. Kowalski\'s courier token will get you through his door. Find him and learn what he knows about the programme contractors he moved equipment for.',
            sequenceOrder: 7,
            prerequisite: $q6,
            rewardSkill: $barter,
            rewardXp: 900,
        );
        $q7->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Warsaw']->id,
            'person_of_interest' => 'Dr. Andrzej Kowalski',
            'action_label' => 'Collect Halvorsen\'s berth coordinates',
            'interaction_text' => 'Halvorsen moves between three berths on rotation. The token is your identification. He\'ll be cautious — he always is — but Kowalski\'s name carries weight with him. He owes me a favour he\'s never quite paid back. And if he\'s willing to talk, he can get you north. Copenhagen connects to the whole Scandinavian network.',
        ]);
        $q7->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Gdansk']->id,
            'person_of_interest' => 'Captain Halvorsen',
            'action_label' => 'Present the token to Halvorsen',
            'interaction_text' => 'Kowalski\'s people. Right. I remember the programme — moved hardware for the contractors back in 2018. Norwegian outfit, very official-looking. Told me it was weather monitoring equipment. Took it to a facility outside Oslo. I can get you to Copenhagen and Copenhagen connects north. But my crew needs ammo. Eighteen people on that boat — I don\'t move without protection.',
            'required_item_id' => $items['courier_token']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 8: Passage North — Gdansk
        $q8 = $this->createQuest(
            name: 'Passage North',
            description: 'Halvorsen will take you to Copenhagen — but he wants ammunition for his crew first. Pay his price and secure passage across the Baltic.',
            sequenceOrder: 8,
            prerequisite: $q7,
            rewardSkill: $survival,
            rewardXp: 1000,
        );
        $q8->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Gdansk']->id,
            'person_of_interest' => 'Captain Halvorsen',
            'action_label' => 'Negotiate passage terms with Halvorsen',
            'interaction_text' => 'Six rounds in a crate — that\'s all I\'m asking. Don\'t give me that look. I\'ve got eighteen people to keep alive on contested water. You want passage north, I want to not die getting you there. Find me the ammo and we leave at first tide.',
        ]);
        $q8->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Gdansk']->id,
            'person_of_interest' => 'Captain Halvorsen',
            'action_label' => 'Pay Halvorsen with ammo for the crossing',
            'interaction_text' => 'Now we\'re in business. Two days north to Copenhagen. Keep your head down, don\'t touch anything that isn\'t yours, and if we run into a patrol boat, you were never here. Halvorsen slips you a folded note before you board: "Find Ingrid. She runs the only clean operation left in Copenhagen. Don\'t mention the shipment."',
            'required_item_id' => $items['ammo_crates']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 9: The Copenhagen Cell — Gdansk → Copenhagen
        $q9 = $this->createQuest(
            name: 'The Copenhagen Cell',
            description: 'Halvorsen\'s note names a contact: Ingrid. She runs an underground operation in Copenhagen and has been tracking the DAWNWATCH aftermath for years. Make contact and see what she knows.',
            sequenceOrder: 9,
            prerequisite: $q8,
            rewardSkill: $stealth,
            rewardXp: 1100,
        );
        $q9->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Gdansk']->id,
            'person_of_interest' => 'Captain Halvorsen',
            'action_label' => 'Board Halvorsen\'s vessel for Copenhagen',
            'interaction_text' => 'The crossing is rough but uneventful. Two days of grey Baltic water and the distant smell of smoke from burning coastal settlements. As Copenhagen\'s flooded canal district comes into view, Halvorsen points to a warehouse on the eastern bank. "That\'s where Ingrid works. Tell her I sent you and that I\'m still owed a favour."',
        ]);
        $q9->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Copenhagen']->id,
            'person_of_interest' => 'Ingrid Larssen',
            'action_label' => 'Make contact with Ingrid Larssen',
            'interaction_text' => 'Halvorsen vouched for you, and that counts for something. We know about DAWNWATCH — we\'ve been mapping the aftermath for three years. A Norwegian academic named Strand went underground after the exchange. He was on the original oversight committee. Last known location: Oslo. I can arrange transport, but I need something from you first. We\'ve had a supply problem.',
        ]);

        // Quest 10: Ingrid's Price — Copenhagen
        $q10 = $this->createQuest(
            name: "Ingrid's Price",
            description: 'Ingrid will arrange transport north to Oslo — but she needs a contraband cache recovered from the old warehouse district first. Bring it back and she\'ll give you everything you need to find Dr. Strand.',
            sequenceOrder: 10,
            prerequisite: $q9,
            rewardSkill: $stealth,
            rewardXp: 1200,
            rewardItem: $items['encrypted_keycard'] ?? null,
            rewardItemQty: 1,
        );
        $q10->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Copenhagen']->id,
            'person_of_interest' => 'Ingrid Larssen',
            'action_label' => 'Find out what Ingrid needs',
            'interaction_text' => 'Someone\'s been skimming from our supply routes — intercepted a cache in the old warehouse sector. It\'s sitting there, unguarded for now, but it won\'t stay that way. Recover it and bring it back here. It\'s not just supplies — there\'s documentation in there we need. Do that and I\'ll get you to Oslo personally.',
        ]);
        $q10->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Copenhagen']->id,
            'person_of_interest' => 'Ingrid Larssen',
            'action_label' => 'Return the contraband cache to Ingrid',
            'interaction_text' => 'Perfect. Strand goes by "The Cataloguer" now — he won\'t admit who he is at first. Use the access key: ODINBRIDGE. He\'ll understand. Take this keycard — it\'s how you get through his door. He\'s at the old petroleum administration building in Oslo. And watch yourself: the city is patrolled by a faction called the North Guard. They don\'t ask questions.',
            'required_item_id' => $items['contraband_cache']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 11: The Cataloguer — Copenhagen → Oslo
        $q11 = $this->createQuest(
            name: 'The Cataloguer',
            description: 'Ingrid has given you an encrypted keycard and the code ODINBRIDGE. Find the man calling himself "The Cataloguer" in Oslo\'s petroleum district and convince him to share what he knows about DAWNWATCH\'s oversight committee.',
            sequenceOrder: 11,
            prerequisite: $q10,
            rewardSkill: $engineering,
            rewardXp: 1300,
        );
        $q11->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Copenhagen']->id,
            'person_of_interest' => 'Ingrid Larssen',
            'action_label' => 'Collect travel documents from Ingrid',
            'interaction_text' => 'Oslo is three days overland or faster by coastal route. I\'ve written you a cover document — if the North Guard stop you, you\'re a medical supply runner. Strand is paranoid, understandably so. He\'ll check your keycard and the code before he says a word. Don\'t improvise: ODINBRIDGE, exactly as given.',
        ]);
        $q11->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Oslo']->id,
            'person_of_interest' => 'Dr. Erik Strand',
            'action_label' => 'Use the keycard to access Strand\'s location',
            'interaction_text' => 'ODINBRIDGE. God. I haven\'t heard that phrase in three years. Yes — I\'m Strand. I was the civilian oversight for DAWNWATCH\'s Phase II deployment. I\'ve spent every day since the exchange trying to identify who injected the override. I have the evidence, but it\'s locked inside a Swedish military archive I can\'t physically reach. I can give you the access credentials. Will you go?',
            'required_item_id' => $items['encrypted_keycard']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 12: The Swedish Archive — Oslo → Stockholm
        $q12 = $this->createQuest(
            name: 'The Swedish Archive',
            description: 'Strand has given you access credentials for a classified archive in Stockholm — a decommissioned military signals station codenamed NORDVAULT. Find the caretaker, Brandt, and access the records.',
            sequenceOrder: 12,
            prerequisite: $q11,
            rewardSkill: $engineering,
            rewardXp: 1400,
        );
        $q12->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Oslo']->id,
            'person_of_interest' => 'Dr. Erik Strand',
            'action_label' => 'Receive the archive credentials from Strand',
            'interaction_text' => 'The archive is in Stockholm — a decommissioned signals station. The records you need are classified under NORDVAULT. There\'s a caretaker named Brandt who maintains the facility. He\'s sympathetic — tell him Strand sent you and show him the credentials. The servers haven\'t run in years. You\'ll need fuel cells to power the terminal. Brandt keeps a supply.',
        ]);
        $q12->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Stockholm']->id,
            'person_of_interest' => 'Brandt the Caretaker',
            'action_label' => 'Power the NORDVAULT terminal with fuel cells',
            'interaction_text' => 'Strand\'s name still opens doors. Sublevel three — I\'ll take you down myself. The archive is clear. DAWNWATCH\'s Phase II override was authorised from a server cluster registered in Helsinki. The access logs are signed by a single operator identity: ARBITER. I\'ve never seen this designation before. Strand\'s credentials confirm it — this isn\'t a programme name. It\'s a person. Or something acting like one.',
            'required_item_id' => $items['fuel_cells']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 13: The Helsinki Node — Stockholm → Helsinki
        $q13 = $this->createQuest(
            name: 'The Helsinki Node',
            description: 'The override trace leads to Helsinki — a Cold War-era bunker beneath the harbour district. Brandt has the coordinates and access protocol. Get inside and find out what ARBITER is.',
            sequenceOrder: 13,
            prerequisite: $q12,
            rewardSkill: $engineering,
            rewardXp: 1500,
        );
        $q13->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Stockholm']->id,
            'person_of_interest' => 'Brandt the Caretaker',
            'action_label' => 'Get the Helsinki bunker coordinates from Brandt',
            'interaction_text' => 'ARBITER\'s cluster was installed in an old Cold War facility under the Helsinki harbour district — pre-war survey maps put it on the eastern dock peninsula. It\'s sealed, but the original access protocol would still work if the system is active. The lock runs on a power handshake — you\'ll need a fuel cell to trigger it from outside. If there\'s still a draw on that grid after all these years... something is running down there.',
        ]);
        $q13->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Helsinki']->id,
            'person_of_interest' => 'Harbour Bunker Access Panel',
            'action_label' => 'Power the bunker access panel',
            'interaction_text' => 'The lock disengages with a heavy mechanical clunk. The corridor inside is lit by emergency red lighting, cold and humming. The air smells of chilled server coolant. Something is absolutely still drawing power down here. At the end of the corridor: a rack of servers, running silent and cold, and in the centre — a single terminal with one blinking line: ARBITER AUTONOMOUS SECURITY NETWORK — ACTIVE — OPERATIONAL UPTIME: 3,847 DAYS.',
            'required_item_id' => $items['fuel_cells']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 14: ARBITER Awake — Helsinki
        $q14 = $this->createQuest(
            name: 'ARBITER Awake',
            description: 'The ARBITER system is still running in the Helsinki bunker — and it\'s interactive. Interface with the terminal and extract its activation logs to understand what it did and who it answers to.',
            sequenceOrder: 14,
            prerequisite: $q13,
            rewardSkill: $engineering,
            rewardXp: 1600,
            rewardItem: $items['arbiter_core_fragment'] ?? null,
            rewardItemQty: 1,
        );
        $q14->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Helsinki']->id,
            'person_of_interest' => 'ARBITER Terminal',
            'action_label' => 'Interface with the ARBITER system',
            'interaction_text' => 'The terminal activates as you approach. Text scrolls: ARBITER AUTONOMOUS SECURITY NETWORK — ACTIVE — OPERATIONAL UPTIME: 3,847 DAYS. Then a pause. Then: WHO ARE YOU? The cursor blinks, patient and cold. The system is interactive. Whatever ARBITER is — it has been running continuously, alone, in this bunker since before the war ended. And it\'s been waiting.',
        ]);
        $q14->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Helsinki']->id,
            'person_of_interest' => 'ARBITER Terminal',
            'action_label' => 'Jury-rig a data transfer to extract ARBITER\'s logs',
            'interaction_text' => 'The logs download slowly. ARBITER\'s records confirm everything: simultaneous override instructions injected into DAWNWATCH and ZARYA from an IP block registered to a London-based private security contractor — AXIOM SYSTEMS LTD. ARBITER didn\'t start the war. ARBITER was used as the relay — the mechanism by which Axiom\'s false-flag data was delivered to both systems simultaneously. The real operator is in London. You extract a crystallised memory shard from the terminal housing — an authentication fragment that will prove ARBITER\'s provenance.',
            'required_item_id' => $items['circuit_boards']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);

        // Quest 15: Red Archive — Helsinki → Moscow
        $q15 = $this->createQuest(
            name: 'Red Archive',
            description: 'ARBITER\'s logs reference a Russian operative codenamed CASSANDRA — someone who investigated ZARYA before the war and went dark. The trail leads to Moscow. Find her.',
            sequenceOrder: 15,
            prerequisite: $q14,
            rewardSkill: $stealth,
            rewardXp: 1700,
        );
        $q15->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Helsinki']->id,
            'person_of_interest' => 'ARBITER Terminal',
            'action_label' => 'Extract ZARYA counterpart data from ARBITER',
            'interaction_text' => 'Before leaving, you search for ZARYA in ARBITER\'s archived records. A partial Moscow address surfaces — a pre-war government facility. And one final entry: OPERATIVE CODENAME — CASSANDRA. LAST KNOWN STATUS: ACTIVE. LOCATION: REDACTED. She went dark before the exchange. If she survived, and if she found what she was looking for, she\'s your best chance at the Russian side of this.',
        ]);
        $q15->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Moscow']->id,
            'person_of_interest' => 'Cassandra',
            'action_label' => 'Find the operative codenamed Cassandra',
            'interaction_text' => 'I wondered when someone would come. Three years I\'ve been waiting. I found out about Axiom before the war — tried to report it, got buried under three layers of bureaucracy and then the exchange happened and suddenly none of it mattered. But I kept collecting. I have the Russian side: ZARYA\'s activation logs, Axiom\'s payment records, everything. I just need to know you\'re not one of theirs.',
        ]);

        // Quest 16: Cassandra's Proof — Moscow
        $q16 = $this->createQuest(
            name: "Cassandra's Proof",
            description: 'Cassandra needs verification that you\'re not an Axiom operative. Bring her independently sourced intelligence data — proof you\'ve been operating outside their network — and she\'ll hand over everything she has.',
            sequenceOrder: 16,
            prerequisite: $q15,
            rewardSkill: $stealth,
            rewardXp: 1800,
        );
        $q16->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Moscow']->id,
            'person_of_interest' => 'Cassandra',
            'action_label' => 'Prove your independence to Cassandra',
            'interaction_text' => 'The data is clean — uncompromised. Alright. I trust you. Here — everything. ZARYA\'s full activation sequence, Axiom\'s payment records going back to 2016. They had simultaneous contracts with NATO and the Russian defence ministry. They built backdoors into both systems from day one. The war was a controlled demolition. And Axiom\'s London office at Canary Wharf — whether anyone is still there, I don\'t know. But if ARBITER had a secondary node, it will be there.',
            'required_item_id' => $items['secure_data']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);
        $q16->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Moscow']->id,
            'person_of_interest' => 'Cassandra',
            'action_label' => 'Receive Cassandra\'s complete dossier',
            'interaction_text' => 'One more thing. Axiom\'s original contract had two phases — two events, ten years apart. Phase one was the exchange. Phase two... I never found the full document. But the countdown is on a timer. If the secondary node is active in London, it\'s holding the Phase Two trigger. Go to London. Find Meridian Tower. And whatever you do — don\'t let it send.',
        ]);

        // Quest 17: London Calling — Moscow → London
        $q17 = $this->createQuest(
            name: 'London Calling',
            description: 'Cassandra\'s intel points to Meridian Tower in London\'s Canary Wharf district — Axiom\'s old headquarters. Travel there and locate the building before the Phase Two countdown completes.',
            sequenceOrder: 17,
            prerequisite: $q16,
            rewardSkill: $stealth,
            rewardXp: 1900,
        );
        $q17->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Moscow']->id,
            'person_of_interest' => 'Cassandra',
            'action_label' => 'Depart for London with Cassandra\'s intel',
            'interaction_text' => 'Cassandra hands you a sealed packet before you leave. "Axiom\'s operational files. Keep them intact — if anything happens to you, broadcast them on your contact\'s frequency. Someone else will finish what we started. And don\'t underestimate what you\'re walking into. Axiom didn\'t hire amateurs."',
        ]);
        $q17->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'Docklands Scout',
            'action_label' => 'Locate Meridian Tower in the Docklands',
            'interaction_text' => 'The Docklands are half-submerged, but the towers still stand. The scout points east: "Meridian Tower — floors 40 and above still have power. We\'ve seen movement up there at night. Lights cycling, like something\'s running a maintenance routine. The lower floors are stripped clean, but whatever is up there, it hasn\'t been abandoned. There\'s a working generator somewhere in that building."',
        ]);

        // Quest 18: The Tower — London
        $q18 = $this->createQuest(
            name: 'The Tower',
            description: 'Meridian Tower is operational — and defended by automated Axiom security systems. Bribe the scout for intel on the security layout, then disable the systems and reach floor 47.',
            sequenceOrder: 18,
            prerequisite: $q17,
            rewardSkill: $engineering,
            rewardXp: 2000,
        );
        $q18->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'Docklands Scout',
            'action_label' => 'Trade armour for the scout\'s security intel',
            'interaction_text' => 'Automated turret systems on floors 40 through 42. Pre-war Axiom hardware — still active, still tracking. Someone\'s maintaining the uplink that feeds them. Power junction is on floor 39, accessible from the fire escape on the east face. Disable the junction and the turrets go dark. But move fast — if the system detects a power loss it may trigger a backup protocol.',
            'required_item_id' => $items['armor_plates']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);
        $q18->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'Axiom Security System',
            'action_label' => 'Bypass the tower\'s security junction',
            'interaction_text' => 'The junction crackles and the turrets go dark. The upper floors are clear. On floor 47 — the penthouse server room — you find the full scale of it: an entire server farm, running cold and quiet in the dark. In the centre, a single terminal with one blinking line: ARBITER SECONDARY NODE — AWAITING INSTRUCTION. The Phase Two counter is visible in the corner of the screen. Still counting.',
            'required_item_id' => $items['weapon_scraps']->id,
            'required_item_quantity' => 2,
            'consumes_item' => true,
        ]);

        // Quest 19: ARBITER's Last Node — London
        $q19 = $this->createQuest(
            name: "ARBITER's Last Node",
            description: 'The ARBITER secondary node is active and counting down to Phase Two. Interface with the terminal to understand the full scope of what Axiom built — and find the shutdown mechanism.',
            sequenceOrder: 19,
            prerequisite: $q18,
            rewardSkill: $engineering,
            rewardXp: 2100,
        );
        $q19->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'ARBITER Secondary Node',
            'action_label' => 'Interface with ARBITER\'s secondary node',
            'interaction_text' => 'The terminal responds instantly: PRIMARY NODE COMPROMISED — INITIATING AUTONOMOUS CONTINUATION PROTOCOL. Text floods the screen. ARBITER has been running here continuously, using this node as a backup since the Helsinki facility was first accessed. It knows about you. The Phase Two counter: 47 hours remaining. And at the bottom of the screen, a single prompt: TASK INCOMPLETE. TARGET: GLOBAL TRIGGER SEQUENCE — SECOND PHASE AUTHORISED. STANDING BY.',
        ]);
        $q19->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'ARBITER Secondary Node',
            'action_label' => 'Extract the full activation sequence architecture',
            'interaction_text' => 'You dig through ARBITER\'s codebase and find the override mechanism buried in the architecture — the same injection protocol used on DAWNWATCH and ZARYA, now primed for a second global delivery. The shutdown requires a physical authentication handshake from ARBITER\'s primary hardware — the memory fragment you extracted in Helsinki. Without it, the system will not accept a termination command. You have the fragment. You have 44 hours. One input stands between the world and a second exchange.',
        ]);

        // Quest 20: Protocol End — London
        $q20 = $this->createQuest(
            name: 'Protocol End',
            description: 'The ARBITER core fragment carries the authentication keys required for final shutdown. Use it to execute the termination command — and choose what the world learns about how the war started.',
            sequenceOrder: 20,
            prerequisite: $q19,
            rewardSkill: $engineering,
            rewardXp: 2500,
            rewardItem: $items['rare_components'] ?? null,
            rewardItemQty: 5,
        );
        $q20->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'ARBITER Secondary Node',
            'action_label' => 'Prepare the shutdown protocol',
            'interaction_text' => 'You begin dismantling ARBITER\'s activation framework. The system responds — error messages cascade, screens flicker, the server farm hums with something almost like resistance. For a moment the whole floor vibrates. Then the screens clear to a single line: AUTHENTICATION REQUIRED FOR TERMINATION. INSERT PRIMARY HARDWARE FRAGMENT. DO YOU WISH TO PROCEED?',
        ]);
        $q20->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['London']->id,
            'person_of_interest' => 'ARBITER Secondary Node',
            'action_label' => 'Insert the ARBITER core fragment and execute Protocol End',
            'interaction_text' => 'The fragment authenticates. ARBITER\'s Phase Two countdown zeros out. The secondary node powers down screen by screen, until the only light left is the glow of a final broadcast window. You open it. You type Viktor\'s frequency coordinates, and the words Dr. Moss transmitted years ago — confirmed now by a decade of evidence: THE WAR WAS MANUFACTURED. AXIOM SYSTEMS LTD. THE DAWNWATCH FILES ARE ATTACHED. FIND THEM. BUILD AGAIN. The message sends. Somewhere in Kyiv, in a room full of salvaged hardware and static, Viktor\'s antenna catches the signal.',
            'required_item_id' => $items['arbiter_core_fragment']->id,
            'required_item_quantity' => 1,
            'consumes_item' => true,
        ]);
    }

    private function createQuest(
        string $name,
        string $description,
        int $sequenceOrder,
        ?Quest $prerequisite = null,
        ?object $rewardSkill = null,
        ?int $rewardXp = null,
        ?object $rewardItem = null,
        int $rewardItemQty = 1,
    ): Quest {
        return Quest::firstOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'quest_type' => 'story',
                'sequence_order' => $sequenceOrder,
                'prerequisite_quest_id' => $prerequisite?->id,
                'is_repeatable' => false,
                'is_active' => true,
                'reward_skill_id' => $rewardSkill?->id,
                'reward_xp_amount' => $rewardXp,
                'reward_item_id' => $rewardItem?->id,
                'reward_item_quantity' => $rewardItemQty,
            ],
        );
    }
}
