import tweepy as tw
import mysql.connector

consumer_key= '5dKiIlmaMdfwEEUxXKJY9RiMr'
consumer_secret= 'y5VjDS2RRraTPRtkBcBMOdM5vTI9qEW5hNeEI0UxtgtWSuahzT'
access_token= '1347832304371073026-fFOIl9RM0B9ajX09OvK934AKaAHFYU'
access_token_secret= '7Itbc0Uw57nJTMZKI9yZa6oQtGq4sl7EnHgCjwkPydK8v'

auth = tw.OAuthHandler(consumer_key, consumer_secret)
auth.set_access_token(access_token, access_token_secret)
api = tw.API(auth, wait_on_rate_limit=True)


db = mysql.connector.connect(
  host="localhost",
  user="root",
  password="",
  database = "test",
  charset='utf8'
)

for tweet in tw.Cursor(api.search, q='vaksinasi OR vaksin OR covid-19 OR sinovac OR astrazeneca -filter:retweets', 
                       tweet_mode='extended', lang="id").items(250):
    created_at = tweet.created_at
    text = tweet.full_text
    # Simpan ke database
    cursor = db.cursor()
    sql = "INSERT INTO crawl (created_at, text) VALUES (%s, %s)"
    cursor.execute(sql, (created_at, text.encode('utf-8')))
    db.commit()